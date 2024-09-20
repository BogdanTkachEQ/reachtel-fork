<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

use Models\Autoload\AutoloadDTO;
use Models\Entities\FixedWidthFile;
use Services\Autoload\AutoloadContext;
use Services\Autoload\CsvAutoloadFileProcessor;
use Services\Autoload\FixedWidthFileProcessor;
use Services\Autoload\GenericFileAutoloadStrategy;
use Services\Autoload\GenericLineExclusionRule;
use Services\Autoload\LineExclusionEvaluator;
use Services\Autoload\PublicHolidayLineExclusionRule;
use Services\Autoload\XlsAutoloadFileProcessor;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\GenericCampaignCreator;
use Services\Campaign\Limits\SendRate\Factory\SendRateCalculatorFactory;
use Services\Container\ContainerAccessor;
use Services\Doctrine\EntityManagerAccessor;
use Services\File\CSV\CSVFactory;
use Services\File\Factory\CryptoFactory;
use Services\Reports\CsvArrayToFileConverter;
use Services\Validators\CompositeRunController;
use Services\Validators\PublicHolidayRunController;
use Services\Validators\WeekendRunController;

/**
 * @param array $tags
 * @return CsvAutoloadFileProcessor|FixedWidthFileProcessor|XlsAutoloadFileProcessor
 * @throws \Doctrine\DBAL\DBALException
 * @throws \Doctrine\ORM\ORMException
 */
function getFileProcessor(array $tags) {
    $tags['filetype'] = isset($tags['filetype']) ? $tags['filetype'] : 'csv';
    if (strtolower($tags['filetype']) === "csv") {
        $csvParser = (new CSVFactory())->createBasicCSV();
        $fileProcessor = new CsvAutoloadFileProcessor($csvParser) ;
        if (isset($tags['csv-header'])) {
            $fileProcessor->setHeaderString($tags['csv-header']);
        }
    } else if (strtolower($tags['filetype']) === 'xls') {
        $fileProcessor = new XlsAutoloadFileProcessor();
    } else {
        /** @var EntityManagerAccessor $em */
        $emAccessor = ContainerAccessor::getContainer()->get(EntityManagerAccessor::class);

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $emAccessor->getEntityManager();

        /** @var FixedWidthFile[] $fixedWidthFiles */
        $fixedWidthFiles = $em->getRepository(FixedWidthFile::class)->findByName($tags['filetype']);

        if (!count($fixedWidthFiles)) {
            sendErrorEmail('Invalid file type ' . $tags['filetype'] . ' mentioned in filetype tag');
            exit(1);
        }

        $specs = $fixedWidthFiles[0]->getSpecifications();
        $fileProcessor = new FixedWidthFileProcessor($specs);
    }

    return $fileProcessor;
}

/**
 * @return GenericCampaignCreator
 */
function getCampainCreator() {
    return new GenericCampaignCreator(new GenericCampaignCloner());
}

/**
 * @param array $tags
 * @return mixed
 * @throws Exception
 */
function getSendRateCalculator(array $tags) {
    $sendRateCalcFactory = ContainerAccessor::getContainer()->get(SendRateCalculatorFactory::class);
    return $sendRateCalcFactory->createByValue(isset($tags['send-rate-calculator']) ? $tags['send-rate-calculator'] : null);
}

/**
 * @param $cronId
 * @return DateTimeZone
 */
function getTimeZone($cronId) {
    return new DateTimeZone(api_cron_setting_getsingle($cronId, "timezone") ?: 'Australia/Sydney');
}

/**
 * @param array        $tags
 * @param DateTimeZone $timeZone
 * @return LineExclusionEvaluator
 * @throws Exception
 */
function getExclusionEvaluator(array $tags, DateTimeZone $timeZone) {

    $exclusionColumns = [];
    foreach ($tags as $key => $value) {
        if (preg_match('/exclusion-column-(.*)/', $key, $matches)) {
            $exclusionColumns[$matches[1]] = array_map('trim', explode(',', $value));
        }
    }
    $exclusionEvaluator = new LineExclusionEvaluator();
    $exclusionEvaluator->addRule(new GenericLineExclusionRule($exclusionColumns));

    if (isset($tags['public-holiday-exclusion-columns'])) {
        $phExclusionColumns = array_map('trim', explode(',', $tags['public-holiday-exclusion-columns']));
        $phExclusionRule = ContainerAccessor::getContainer()
            ->get(PublicHolidayLineExclusionRule::class)
            ->setCountryColumnNames($phExclusionColumns)
            ->setDateTime(new DateTime('now', $timeZone));
        $exclusionEvaluator->addRule($phExclusionRule);
    }

    return $exclusionEvaluator;
}

/**
 * @param AutoloadDTO $autoloadDto
 * @param array       $tags
 * @return AutoloadDTO
 */
function buildDto(AutoloadDTO $autoloadDto, array $tags) {
    if (isset($tags['file-date-column'])) {
        $autoloadDto->setCallDateColumnName($tags['file-date-column']);
    }

    if (isset($tags['next-attempt-time'])) {
        $autoloadDto->setNextAttemptTime($tags['next-attempt-time']);
    }

    if (isset($tags['file-destination-column'])) {
        $autoloadDto->setDestinationColumnName($tags['file-destination-column']);
    }

    if (isset($tags['alternative-destination-fields'])) {
        $alternativeDestinations = array_map('trim', explode(',', $tags['alternative-destination-fields']));
        $autoloadDto->setAlternativeDestinationColumnNames($alternativeDestinations);
    }

    if (isset($tags['target-key-column'])) {
        $autoloadDto->setTargetKeyColumnName($tags['target-key-column']);
    }

    if (isset($tags['send-rate-modifier'])) {
        $autoloadDto->setSendRateHourBuffer($tags['send-rate-modifier']);
    }

    return $autoloadDto;
}

/**
 * @param      $content
 * @param null $destination
 * @return bool
 */
function sendErrorEmail($content, $destination = null)
{
    print $content;
    $email = [
        'to' => $destination ?: 'ReachTEL Support <support@ReachTEL.com.au>',
        'cc' => 'ReachTEL Support <support@ReachTEL.com.au>',
        'from' => 'ReachTEL Support <support@ReachTEL.com.au>',
        'subject' => '[ReachTEL] Auto-load error - Generic Date Based',
    ];

    $email['content'] = $content;
    return api_email_template($email);
}

/**
 * @param GenericFileAutoloadStrategy $strategy
 * @param array                       $tags
 * @param DateTimeZone                $timeZone
 * @param                             $filename
 * @return AutoloadContext
 * @throws Exception
 */
function buildAutoloadContext(GenericFileAutoloadStrategy $strategy, array $tags, DateTimeZone $timeZone, $filename) {
    $date = new DateTime('now', $timeZone);

    $weekendRunController = new WeekendRunController($date);
    $compositeRuncontroller = new CompositeRunController();
    $compositeRuncontroller->addRunController($weekendRunController);

    if (isset($tags['disable-public-holiday-protection'])) {
        print "Disabled public holiday protection by request\n";
    } else {
        $publicHolidayRunController = new PublicHolidayRunController($date);
        $compositeRuncontroller->addRunController($publicHolidayRunController);
    }

    $decryptor = null;

    if (isset($tags['decryptor'])) {
        $decryptor = CryptoFactory::create($tags['decryptor']);

        if (isset($tags['decryptor-keys'])) {
            $keys = explode(',', $tags['decryptor-keys']);
            $decryptor->setKeys($keys);
        }
    }

    $context = new AutoloadContext(
        $strategy,
        [
            AutoloadContext::SFTP_HOSTNAME_KEY => $tags['sftp-hostname'],
            AutoloadContext::SFTP_USERNAME_KEY => $tags['sftp-username'],
            AutoloadContext::SFTP_PASSWORD_KEY => $tags['sftp-password'],
            AutoloadContext::SFTP_PATH_KEY => $tags['sftp-path']
        ],
        $compositeRuncontroller,
        $decryptor
    );

    $context
        ->setFailureNotificationEmail($tags["failure-notification-email"])
        ->setFailureNotificationSubject('[ReachTEL] Generic Auto-load error - ' . $filename)
        ->process($filename);

    handleBadRecords($strategy, $tags, $filename);

    return $context;
}

/**
 * @param GenericFileAutoloadStrategy $strategy
 * @param array                       $tags
 * @param string                      $filename
 * @return boolean
 * @throws Exception
 */
function handleBadRecords(GenericFileAutoloadStrategy $strategy, array $tags, $filename) {
    if (
        !isset($tags['badrecords-sftp-username']) ||
        !isset($tags['badrecords-sftp-password']) ||
        !isset($tags['badrecords-sftp-path']) ||
        !isset($tags['badrecords-sftp-hostname'])
    ) {
        return true;
    }

    $tempfname = tempnam(FILEPROCESS_TMP_LOCATION, "badrecord");

    $csv = ContainerAccessor::getContainer()->get(CsvArrayToFileConverter::class);

    $pathinfo = pathinfo($filename);
    $filename = $pathinfo['filename'] . '_badrecords.csv';

    try {
        if (isset($tags['encryptor']) && isset($tags['encryptor-keys'])) {
            $filename .= '.' . $tags['encryptor'];
            $encryptor = CryptoFactory::create($tags['encryptor']);
            $encryptor->setKeys(explode(',', $tags['encryptor-keys']));
        } else {
            $encryptor = null;
        }

        $strategy->writeBadRecordsToFile($csv, $tempfname, $encryptor);
    } catch (RuntimeException $exception) {
        return true;
    } catch (Exception $exception) {
        print $exception->getMessage();
        return true;
    }

    $options = array("hostname"  => $tags["badrecords-sftp-hostname"],
        "username"  => $tags["badrecords-sftp-username"],
        "password"  => $tags["badrecords-sftp-password"],
        "localfile" => $tempfname,
        "remotefile" => $tags["badrecords-sftp-path"] . $filename);

    api_misc_sftp_put($options);
    unlink($tempfname);

    return true;
}
