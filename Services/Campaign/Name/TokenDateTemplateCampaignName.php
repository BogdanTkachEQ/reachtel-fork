<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Name;

use DateTime;
use InvalidArgumentException;
use Services\Campaign\Name\Interfaces\CampaignNameInterface;
use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\Validators\CampaignNameValidator;

/**
 * Class TokenDateCampaignNameCreator
 * Given a campaign name template, a text token and date with format build a campaign name
 * E.g
 * $template = {PREFIX:MakeAWish}-{TOKEN}-{DATE:Ymd}
 * $token = "VOL"
 * $date = "20190101"
 * name output = MakeAWish-VOL-20190101
 *
 * $template = {PREFIX:MakeAWish}-{DATE:YM}-{TOKEN}
 * $token = "VOL"
 * $date = "20190101"
 * name output = MakeAWish-2019JAN-VOL
 *
 */
class TokenDateTemplateCampaignName implements CampaignNameInterface
{

    private $campaignNameTemplate;
    private $token;
    private $date;
    /**
     * @var null
     */
    private $dateFormat;

    /** @var CampaignNameValidator */
    private $campaignNameValidator;

    public function __construct(
        $campaignNameTemplate,
        $token,
        DateTime $date,
        CampaignNameValidator $campaignNameValidator
    ) {
        $this->campaignNameTemplate = $campaignNameTemplate;
        $this->token = $token;
        $this->date = $date;
        $this->campaignNameValidator = $campaignNameValidator;
    }

    /**
     *
     * Compile the name based on the template and input values
     * The date portion of the name can be overridden here so we can build
     * things like the next campaign name in a series, the searchable campaign name
     * (e.g MakeAWish-Vol-*), etc
     *
     * @param null $overrideDateValue
     * @return string|string[]|null
     */
    private function createName($overrideDateValue = null)
    {
        $this->validateTemplate();

        $campaignDate = $this->date->format($this->getDateFormat());
        if (!$campaignDate) {
            throw new InvalidArgumentException("Invalid date format provided {$this->dateFormat}");
        }
        $campaignName = preg_replace("/\{PREFIX:.*\}/iU", $this->getPrefixValue(), $this->campaignNameTemplate);
        $campaignName = preg_replace("/\{TOKEN\}/iU", $this->token, $campaignName);
        if (!$overrideDateValue) {
            $name = preg_replace("/\{DATE:.*\}/iU", $campaignDate, $campaignName);
            try {
                return $this->campaignNameValidator->setName($name)->sanitizeName();
            } catch (ValidatorRuntimeException $exception) {
                throw new InvalidArgumentException(
                    "Invalid token or date format received. A valid campaign name can not be generated."
                );
            }
        } else {
            $tokens = preg_split("/\{DATE:.*\}/iU", $campaignName);
            if (count($tokens) === 1) {
                return preg_replace("/\{DATE:.*\}/iU", $overrideDateValue, $campaignName);
            }

            foreach ($tokens as &$token) {
                try {
                    if ($token !== '') {
                        $token = $this->campaignNameValidator->setName($token)->sanitizeName();
                    }
                } catch (ValidatorRuntimeException $exception) {
                    throw new InvalidArgumentException(
                        "Invalid searchable campaign name received. A valid campaign name can not be generated."
                    );
                }
            }

            $name = implode($overrideDateValue, $tokens);
            return $name;
        }
    }

    /**
     * @return string|string[]|null
     */
    public function getName()
    {
        return $this->createName();
    }

    /**
     * @return string|string[]|null
     */
    public function getSearchableName()
    {
        return $this->createName("*");
    }

    /**
     * @return bool
     */
    public function validateTemplate()
    {
        if (!preg_match("/\{DATE:.*\}/U", $this->campaignNameTemplate)) {
            throw new InvalidArgumentException("No valid DATE tag specified in the campaign name template");
        }
        if (!preg_match("/\{PREFIX:.*\}/U", $this->campaignNameTemplate)) {
            throw new InvalidArgumentException("No PREFIX tag specified in the campaign name template");
        }
        return true;
    }

    /**
     * @return mixed|string
     */
    public function getPrefixValue()
    {
        $matches = [];
        if (preg_match("/\{PREFIX:(.*)\}/U", $this->campaignNameTemplate, $matches)) {
            return isset($matches[1]) ? $matches[1] : "";
        }
        throw new InvalidArgumentException("No PREFIX tag specified in the campaign name template");
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    private function getDateFormat()
    {
        $matches = [];
        if (preg_match("/\{DATE:(.*)\}/Ui", $this->campaignNameTemplate, $matches)) {
            $format = $matches[1];
            if (!$format) {
                throw new InvalidArgumentException(
                    "{$this->campaignNameTemplate} does not contain a valid date format"
                );
            }
            return $format;
        } else {
            throw new InvalidArgumentException(
                "{$this->campaignNameTemplate} does not contain a valid date tag"
            );
        }
    }
}
