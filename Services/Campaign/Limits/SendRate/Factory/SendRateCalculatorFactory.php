<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Limits\SendRate\Factory;

use Services\Campaign\Limits\SendRate\PercentBoostTimeRemainingSendRateCalc;
use Services\Campaign\Limits\SendRate\SendRateCalc;
use Services\Campaign\Limits\SendRate\SendRateCalculatorEnum;
use Services\Campaign\Limits\SendRate\TimeRemainingSendRateCalc;

/**
 * Class SendRateCalculatorFactory
 */
class SendRateCalculatorFactory
{
    /**
     * @param SendRateCalculatorEnum $calculatorEnum
     * @return SendRateCalc
     */
    public function create(SendRateCalculatorEnum $calculatorEnum = null)
    {
        switch ($calculatorEnum) {
            case SendRateCalculatorEnum::PERCENT_BOOST_TIME_REMAINING():
                return new PercentBoostTimeRemainingSendRateCalc();

            case SendRateCalculatorEnum::TIME_REMAINING():
            default:
                return new TimeRemainingSendRateCalc();
        }
    }

    /**
     * @param string $calculatorType
     * @return SendRateCalc
     */
    public function createByValue($calculatorType = null)
    {
        try {
            $enum = SendRateCalculatorEnum::byValue($calculatorType);
        } catch (\Exception $exception) {
            $enum = null;
        }

        return $this->create($enum);
    }
}
