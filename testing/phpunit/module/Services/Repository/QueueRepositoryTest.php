<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Repository;

use DateTime;
use Doctrine\ORM\EntityManager;
use Models\Entities\QueueItem;
use Services\Container\ContainerAccessor;
use Services\Queue\QueueProcessTypeEnum;
use testing\module\AbstractPhpunitModuleTest;

/**
 * Class QueueRepositoryTest
 */
class QueueRepositoryTest extends AbstractPhpunitModuleTest{
	/**
	 * @return void
	 */
	public function test_get_latest_runnable() {
		$queue_item = new QueueItem();
		$queue_item->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(2)
			->setCampaignId(3)
			->setCreatedAt(new \DateTime())
			->setCanRun(1);
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->persist($queue_item);
		$queue_item1 = new QueueItem();
		$queue_item1->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(2)
			->setPriority(2)
			->setCampaignId(4)
			->setCreatedAt(new DateTime())
			->setCanRun(0);
		$em->persist($queue_item1);
		$em->flush();
		$data = $em->getRepository(QueueItem::class)->findNextRunnableByProcessType(QueueProcessTypeEnum::FILEUPLOAD());

		$this->assertNotEmpty($data);
		foreach ($data as $runnable_queue_item) {
			$this->assertNotEquals($runnable_queue_item->getId(), $queue_item1);
		}

		$em->remove($queue_item);
		$em->remove($queue_item1);
		$em->flush();
	}

	/**
	 * @return void
	 */
	public function test_get_latest_runnable_removed() {
		$queue_item = new QueueItem();
		$queue_item->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(2)
			->setCampaignId(3)
			->setCreatedAt(new DateTime())
			->setCanRun(0);
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->persist($queue_item);
		$em->flush();
		$data = $em->getRepository(QueueItem::class)->findNextRunnableByProcessType(QueueProcessTypeEnum::FILEUPLOAD());
		$id = $queue_item->getId();
		$em->remove($queue_item);
		$em->flush();

		$data = $em->getRepository(QueueItem::class)->find($id);
		$this->assertEmpty($data);
	}

	/**
	 * @return array
	 */
	public function deleteProvider() {
		return [
			['2019-01-01', '2020-12-01', 1],
			['2014-01-01', '2020-12-01', 2],
			['2014-01-01', '2016-12-01', 1],
			['2000-01-01', '2020-12-30', 3]
		];
	}

	/**
	 * @dataProvider deleteProvider
	 * @param string  $start
	 * @param string  $end
	 * @param integer $deleted
	 * @return void
	 */
	public function test_delete($start, $end, $deleted) {
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$queue_item = new QueueItem();
		$queue_item->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(2)
			->setCampaignId(3)
			->setCreatedAt(new DateTime("2020-01-01"))
			->setCanRun(0);
		$em->persist($queue_item);
		$em->flush();

		$queue_item = new QueueItem();
		$queue_item->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(2)
			->setCampaignId(3)
			->setCreatedAt(new DateTime("2015-01-01"))
			->setCanRun(0);
		$em->persist($queue_item);
		$em->flush();

		$queue_item = new QueueItem();
		$queue_item->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
			->setUserId(1)
			->setPriority(2)
			->setCampaignId(3)
			->setCreatedAt(new DateTime("2010-01-01"))
			->setCanRun(0);
		$em->persist($queue_item);
		$em->flush();

		$data = $em->getRepository(QueueItem::class)->deleteBetweenDates(new DateTime($start), new DateTime($end));
		$this->assertEquals($deleted, $data);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->getRepository(QueueItem::class)->deleteBetweenDates(new DateTime("1900-01-01"), new DateTime());
	}
}
