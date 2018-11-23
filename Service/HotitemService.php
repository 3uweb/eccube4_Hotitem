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

namespace Plugin\Hotitem4\Service;

use Plugin\Hotitem4\Entity\HotitemProduct;
use Plugin\Hotitem4\Repository\HotitemProductRepository;

/**
 * Class HotitemService.
 */
class HotitemService
{
    /**
     * @var HotitemProductRepository
     */
    private $hotitemProductRepository;

    /**
     * HotitemService constructor.
     *
     * @param HotitemProductRepository $hotitemProductRepository
     */
    public function __construct(HotitemProductRepository $hotitemProductRepository)
    {
        $this->hotitemProductRepository = $hotitemProductRepository;
    }

    /**
     * 新着商品情報を新規登録する
     *
     * @param $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function createHotitem($data)
    {
        // 新着商品詳細情報を生成する
        $Hotitem = $this->newHotitem($data);

        return $this->hotitemProductRepository->saveHotitem($Hotitem);
    }

    /**
     * 新着商品情報を更新する
     *
     * @param $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function updateHotitem($data)
    {
        // 新着商品情報を取得する
        $Hotitem = $this->hotitemProductRepository->find($data['id']);
        if (!$Hotitem) {
            return false;
        }

        // 新着商品情報を書き換える
        $Hotitem->setComment($data['comment']);
        $Hotitem->setProduct($data['Product']);

        // 新着商品情報を更新する
        return $this->hotitemProductRepository->saveHotitem($Hotitem);
    }

    /**
     * 新着商品情報を生成する
     *
     * @param $data
     *
     * @return HotitemProduct
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function newHotitem($data)
    {
        $rank = $this->hotitemProductRepository->getMaxRank();

        $Hotitem = new HotitemProduct();
        $Hotitem->setComment($data['comment']);
        $Hotitem->setProduct($data['Product']);
        $Hotitem->setSortno(($rank ? $rank : 0) + 1);
        $Hotitem->setVisible(true);

        return $Hotitem;
    }
}
