<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Queue;

use DateTime;
use Doctrine\ORM\EntityManager;
use Models\Entities\QueueItem;
use Services\Container\ContainerAccessor;
use Services\Doctrine\EntityManagerAccessor;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use testing\module\AbstractDatabasePhpunitModuleTest;

/**
 * Class QueueManagerTest
 */
class QueueManagerTest extends AbstractDatabasePhpunitModuleTest {

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->getRepository(QueueItem::class)->deleteBetweenDates(new DateTime("1900-01-01"), new DateTime());
		$emA = ContainerAccessor::getContainer()->get(EntityManagerAccessor::class);
		$emA->reset();
	}

	/**
	 * @return void
	 */
	public function testAddAttachment() {
		$queueItem = new QueueItem();
		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->persist($queueItem);
		$em->flush();
		$qm = new QueueManager($em);
		$tmp = "/tmp/test-data.csv";
		file_put_contents($tmp, "test123");
		$file = new File($tmp);
		$qf = $qm->addAttachment($queueItem, "test-data.csv", $file);
		unlink($file->getPathname());

		$this->assertEquals($qf->getFileName(), "test-data.csv");
		$this->assertEquals($queueItem->getId(), $qf->getQueueId());
		$this->assertEquals($qf->getData(), "test123");

		$em->clear();
	}

	/**
	 * @return void
	 */
	public function testAddAttachmentBadFile() {
		$queueItem = new QueueItem();
		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->persist($queueItem);
		$em->flush();
		$qm = new QueueManager($em);
		$tmp = "/tmp/test-data.csv";
		$file = new File($tmp, false);

		$this->expectException(FileException::class);
		$qf = $qm->addAttachment($queueItem, "test-data.csv", $file);

		$em->clear();
	}

	/**
	 * @return void
	 */
	public function testEndRun() {
		$queueItem = new QueueItem();

		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$qm = new QueueManager($em);
		$qi = $qm->endRun($queueItem, QueueProcessStatusEnum::FAIL()->getValue(), "test", json_encode(["test", "test1"]));

		$em->clear();

		$dbQi = $em->getRepository(QueueItem::class)->find($qi->getId());

		$this->assertFalse($dbQi->isCanRun());
		$this->assertFalse($dbQi->isRunning());
		$this->assertNotEmpty($dbQi->getCompletedAt());
		$this->assertNotEmpty($dbQi->getId());
		$this->assertEquals("test", $dbQi->getReturnText());
		$this->assertEquals(json_encode(["test", "test1"]), $dbQi->getData());
		$this->assertEquals(json_encode($dbQi), json_encode($qi));
	}

	/**
	 * @return void
	 */
	public function testFailedToRun() {
		$queueItem = new QueueItem();

		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$qm = new QueueManager($em);
		$qi = $qm->failedToRun(
			$queueItem,
			QueueProcessStatusEnum::FAIL()->getValue(),
			"failed to run",
			json_encode(["test", "test1"])
		);

		$em->clear();

		$dbQi = $em->getRepository(QueueItem::class)->find($qi->getId());

		$this->assertFalse($dbQi->isCanRun());
		$this->assertFalse($dbQi->isRunning());
		$this->assertEmpty($dbQi->getCompletedAt());
		$this->assertEmpty($dbQi->getRanAt());
		$this->assertNotEmpty($dbQi->getId());
		$this->assertEquals("failed to run", $dbQi->getReturnText());
		$this->assertEquals(json_encode(["test", "test1"]), $dbQi->getData());
		$this->assertEquals(json_encode($dbQi), json_encode($qi));
	}

	/**
	 * @return void
	 */
	public function testRequeue() {
		$queueItem = new QueueItem();

		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$qm = new QueueManager($em);
		$newItem = $qm->requeue($queueItem);

		$em->clear();

		$dbQi = $em->getRepository(QueueItem::class)->find($queueItem->getId());

		$this->assertNotEmpty($dbQi);
		$this->assertNotEquals($queueItem->getId(), $newItem->getId());
		$this->assertTrue($newItem->isCanRun());
		$this->assertFalse($newItem->isHasRun());
		$this->assertEmpty($newItem->getCompletedAt());
		$this->assertFalse($newItem->isRunning());

		$this->assertEquals($queueItem->getUserId(), $newItem->getUserId());
		$this->assertEquals($queueItem->getCampaignId(), $newItem->getCampaignId());
		$this->assertEquals($queueItem->getCreatedAt(), $newItem->getCreatedAt());
	}

	/**
	 * @return void
	 */
	public function testPersistToQueue() {
		$queueItem = new QueueItem();

		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$qm = new QueueManager($em);
		$qm->persistToQueue($queueItem);

		$em->clear();

		$dbQi = $em->getRepository(QueueItem::class)->find($queueItem->getId());

		$this->assertNotEmpty($dbQi);
		$this->assertEquals(json_encode($dbQi), json_encode($queueItem));
	}

	/**
	 * @return void
	 */
	public function testStartRun() {
		$queueItem = new QueueItem();

		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$qm = new QueueManager($em);
		$qi = $qm->startRun($queueItem);

		$this->assertFalse($qi->isCanRun());
		$this->assertEquals(true, $qi->isRunning());
		$this->assertNotEmpty($qi->getRanAt());
		$this->assertNotEmpty($queueItem->getId());

		$em->clear();

		$dbQi = $em->getRepository(QueueItem::class)->find($qi->getId());

		$this->assertNotEmpty($dbQi);
		$this->assertEquals(json_encode($dbQi), json_encode($qi));
	}

	/**
	 * @return void
	 */
	public function testCanRunNow() {
		$queueItem = new QueueItem();
		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(200)
			->setCreatedAt(new DateTime())
			->setIsRunning(false);

		$queueItem1 = new QueueItem();
		$queueItem1->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(200)
			->setCreatedAt(new DateTime())
			->setIsRunning(true);

		$queueItem2 = new QueueItem();
		$queueItem2->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(2)
			->setPriority(0)
			->setCampaignId(200)
			->setCreatedAt(new DateTime())
			->setIsRunning(false);

		$queueItem3 = new QueueItem();
		$queueItem3->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(3)
			->setPriority(0)
			->setCampaignId(901)
			->setCreatedAt(new DateTime())
			->setIsRunning(false);

		$queueItem4 = new QueueItem();
		$queueItem4->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(3)
			->setPriority(0)
			->setCampaignId(902)
			->setCreatedAt(new DateTime())
			->setIsRunning(true);

		$queueItem5 = new QueueItem();
		$queueItem5->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(4)
			->setPriority(0)
			->setCampaignId(905)
			->setCreatedAt(new DateTime())
			->setIsRunning(false);

		$queueItem6 = new QueueItem();
		$queueItem6->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(4)
			->setPriority(0)
			->setCampaignId(905)
			->setCreatedAt(new DateTime())
			->setIsRunning(false);

		$queueItem7 = new QueueItem();
		$queueItem7->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(5)
			->setPriority(0)
			->setCampaignId(905)
			->setCreatedAt(new DateTime())
			->setIsRunning(false);

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);

		$qm = new QueueManager($em);
		$qm->persistToQueue($queueItem);
		$qm->persistToQueue($queueItem1);
		$qm->persistToQueue($queueItem2);
		$qm->persistToQueue($queueItem3);
		$qm->persistToQueue($queueItem4);
		$qm->persistToQueue($queueItem5);
		$qm->persistToQueue($queueItem6);
		$qm->persistToQueue($queueItem7);
		$this->assertFalse($qm->canRunNow($queueItem));
		$this->assertFalse($qm->canRunNow($queueItem1));
		$this->assertFalse($qm->canRunNow($queueItem2));
		$this->assertFalse($qm->canRunNow($queueItem3));
		$this->assertFalse($qm->canRunNow($queueItem4));
		$this->assertTrue($qm->canRunNow($queueItem5));
		$this->assertTrue($qm->canRunNow($queueItem6));
		$this->assertTrue($qm->canRunNow($queueItem7));

		$em->remove($queueItem);
		$em->remove($queueItem1);
		$em->remove($queueItem2);
		$em->remove($queueItem3);
		$em->remove($queueItem4);
		$em->remove($queueItem5);
		$em->flush();
	}

	/**
	 * @return void
	 */
	public function testForceEndRun() {
		$queueItem = new QueueItem();

		$queueItem->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(0)
			->setCampaignId(100)
			->setCreatedAt(new DateTime());

		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$qm = new QueueManager($em);
		$qi = $qm->startRun($queueItem);
		$this->assertEquals(true, $qi->isRunning());
		$qm->forceEndRun($qi);
		$em->clear();

		$dbQi = $em->getRepository(QueueItem::class)->find($qi->getId());

		$this->assertNotEmpty($dbQi);
		$this->assertEquals(false, $dbQi->isRunning());
		$this->assertEquals(-2, $dbQi->getReturnCode());
		$this->assertJson(json_encode(["There has been a problem with this process - forced it to end."]), $dbQi->getData());
	}
}
