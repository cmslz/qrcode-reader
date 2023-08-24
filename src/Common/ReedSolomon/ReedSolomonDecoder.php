<?php

namespace Cmslz\QrcodeReader\Common\ReedSolomon;

use Exception;

final class ReedSolomonDecoder
{

    private $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    /**
     * <p>Decodes given set of received codewords, which include both data and error-correction
     * codewords. Really, this means it uses Reed-Solomon to detect and correct errors, in-place,
     * in the input.</p>
     * @throws ReedSolomonException if decoding fails for any reason
     */
    public function decode(&$received, $twoS)
    {
        $poly = new GenericGFPoly($this->field, $received);
        $syndromeCoefficients = fill_array(0, $twoS, 0);
        $noError = true;
        for ($i = 0; $i < $twoS; $i++) {
            $eval = $poly->evaluateAt($this->field->exp($i + $this->field->getGeneratorBase()));
            $syndromeCoefficients[count($syndromeCoefficients) - 1 - $i] = $eval;
            if ($eval != 0) {
                $noError = false;
            }
        }
        if ($noError) {
            return;
        }
        $syndrome = new GenericGFPoly($this->field, $syndromeCoefficients);
        $sigmaOmega =
            $this->runEuclideanAlgorithm($this->field->buildMonomial($twoS, 1), $syndrome, $twoS);
        $sigma = $sigmaOmega[0];
        $omega = $sigmaOmega[1];
        $errorLocations = $this->findErrorLocations($sigma);
        $errorMagnitudes = $this->findErrorMagnitudes($omega, $errorLocations);
        for ($i = 0; $i < count($errorLocations); $i++) {
            $position = count($received) - 1 - $this->field->log($errorLocations[$i]);
            if ($position < 0) {
                throw new ReedSolomonException("Bad error location");
            }
            $received[$position] = GenericGF::addOrSubtract($received[$position], $errorMagnitudes[$i]);
        }

    }

    /**
     * @throws ReedSolomonException
     * @throws Exception
     */
    private function runEuclideanAlgorithm($a, $b, $R)
    {
        // Assume a's degree is >= b's
        if ($a->getDegree() < $b->getDegree()) {
            $temp = $a;
            $a = $b;
            $b = $temp;
        }

        $rLast = $a;
        $r = $b;
        $tLast = $this->field->getZero();
        $t = $this->field->getOne();

        // Run Euclidean algorithm until r's degree is less than R/2
        while ($r->getDegree() >= $R / 2) {
            $rLastLast = $rLast;
            $tLastLast = $tLast;
            $rLast = $r;
            $tLast = $t;

            // Divide rLastLast by rLast, with quotient in q and remainder in r
            if ($rLast->isZero()) {
                // Oops, Euclidean algorithm already terminated?
                throw new ReedSolomonException("r_{i-1} was zero");
            }
            $r = $rLastLast;
            $q = $this->field->getZero();
            $denominatorLeadingTerm = $rLast->getCoefficient($rLast->getDegree());
            $dltInverse = $this->field->inverse($denominatorLeadingTerm);
            while ($r->getDegree() >= $rLast->getDegree() && !$r->isZero()) {
                $degreeDiff = $r->getDegree() - $rLast->getDegree();
                $scale = $this->field->multiply($r->getCoefficient($r->getDegree()), $dltInverse);
                $q = $q->addOrSubtract($this->field->buildMonomial($degreeDiff, $scale));
                $r = $r->addOrSubtract($rLast->multiplyByMonomial($degreeDiff, $scale));
            }

            $t = $q->multiply($tLast)->addOrSubtract($tLastLast);

            if ($r->getDegree() >= $rLast->getDegree()) {
                throw new Exception("Division algorithm failed to reduce polynomial?");
            }
        }

        $sigmaTildeAtZero = $t->getCoefficient(0);
        if ($sigmaTildeAtZero == 0) {
            throw new ReedSolomonException("sigmaTilde(0) was zero");
        }

        $inverse = $this->field->inverse($sigmaTildeAtZero);
        $sigma = $t->multiply($inverse);
        $omega = $r->multiply($inverse);
        return array($sigma, $omega);
    }

    /**
     * @throws ReedSolomonException
     */
    private function findErrorLocations($errorLocator)
    {
        // This is a direct application of Chien's search
        $numErrors = $errorLocator->getDegree();
        if ($numErrors == 1) { // shortcut
            return array($errorLocator->getCoefficient(1));
        }
        $result = fill_array(0, $numErrors, 0);
        $e = 0;
        for ($i = 1; $i < $this->field->getSize() && $e < $numErrors; $i++) {
            if ($errorLocator->evaluateAt($i) == 0) {
                $result[$e] = $this->field->inverse($i);
                $e++;
            }
        }
        if ($e != $numErrors) {
            throw new ReedSolomonException("Error locator degree does not match number of roots");
        }
        return $result;
    }

    private function findErrorMagnitudes($errorEvaluator, $errorLocations)
    {
        $s = count($errorLocations);
        $result = fill_array(0, $s, 0);
        for ($i = 0; $i < $s; $i++) {
            $xiInverse = $this->field->inverse($errorLocations[$i]);
            $denominator = 1;
            for ($j = 0; $j < $s; $j++) {
                if ($i != $j) {
                    $term = $this->field->multiply($errorLocations[$j], $xiInverse);
                    $termPlus1 = ($term & 0x1) == 0 ? $term | 1 : $term & ~1;
                    $denominator = $this->field->multiply($denominator, $termPlus1);
                }
            }
            $result[$i] = $this->field->multiply($errorEvaluator->evaluateAt($xiInverse),
                $this->field->inverse($denominator));
            if ($this->field->getGeneratorBase() != 0) {
                $result[$i] = $this->field->multiply($result[$i], $xiInverse);
            }
        }
        return $result;
    }

}
