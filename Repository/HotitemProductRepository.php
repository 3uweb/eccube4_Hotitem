<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Hotitem4\Repository;

use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\AbstractRepository;
use Plugin\Hotitem4\Entity\HotitemProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * HotitemProductRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HotitemProductRepository extends AbstractRepository
{
    /**
     * CouponRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HotitemProduct::class);
    }

    /**
     * Find list.
     *
     * @return mixed
     */
    public function getHotitemList()
    {
        $qb = $this->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p');
        $qb->where('rp.visible = true');
        $qb->addOrderBy('rp.sort_no', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get max rank.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxRank()
    {
        $qb = $this->createQueryBuilder('rp')
            ->select('MAX(rp.sort_no) AS max_rank');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get hotitem product by display status of product.
     *
     * @return array
     */
    public function getHotitemProduct()
    {
        $query = $this->createQueryBuilder('rp')
            ->innerJoin('Eccube\Entity\Product', 'p', 'WITH', 'p.id = rp.Product')
            ->where('p.Status = :Disp')
            ->andWhere('rp.visible = true')
            ->orderBy('rp.sort_no', 'DESC')
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Number of hotitem.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countHotitem()
    {
        $qb = $this->createQueryBuilder('rp');
        $qb->select('COUNT(rp)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Move rank.
     *
     * @param array $arrRank
     *
     * @return array
     *
     * @throws \Exception
     */
    public function moveHotitemRank(array $arrRank)
    {
        $this->getEntityManager()->beginTransaction();
        $arrRankMoved = [];
        try {
            foreach ($arrRank as $hotitemId => $rank) {
                /* @var $Hotitem HotitemProduct */
                $Hotitem = $this->find($hotitemId);
                if ($Hotitem->getSortno() == $rank) {
                    continue;
                }
                $arrRankMoved[$hotitemId] = $rank;
                $Hotitem->setSortno($rank);
                $this->getEntityManager()->persist($Hotitem);
            }
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }

        return $arrRankMoved;
    }

    /**
     * Save hotitem.
     *
     * @param HotitemProduct $HotitemProduct
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function saveHotitem(HotitemProduct $HotitemProduct)
    {
        $this->getEntityManager()->beginTransaction();
        try {
            $this->getEntityManager()->persist($HotitemProduct);
            $this->getEntityManager()->flush($HotitemProduct);
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Get all id of hotitem product.
     *
     * @return array
     */
    public function getHotitemProductIdAll()
    {
        $query = $this->createQueryBuilder('rp')
            ->select('IDENTITY(rp.Product) as id')
            ->where('rp.visible = true')
            ->getQuery();
        $arrReturn = $query->getScalarResult();

        return array_map('current', $arrReturn);
    }

    /**
     * 人気商品情報を削除する
     *
     * @param HotitemProduct $HotitemProduct
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteHotitem(HotitemProduct $HotitemProduct)
    {
        // 人気商品情報を書き換える
        $HotitemProduct->setVisible(false);

        // 人気商品情報を登録する
        return $this->saveHotitem($HotitemProduct);
    }
}
