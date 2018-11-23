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

namespace Plugin\Hotitem4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Admin\SearchProductType;
use Plugin\Hotitem4\Entity\HotitemProduct;
use Plugin\Hotitem4\Form\Type\HotitemProductType;
use Plugin\Hotitem4\Repository\HotitemProductRepository;
use Plugin\Hotitem4\Service\HotitemService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HotitemController.
 */
class HotitemController extends AbstractController
{
    /**
     * @var HotitemProductRepository
     */
    private $hotitemProductRepository;

    /**
     * @var HotitemService
     */
    private $hotitemService;

    /**
     * HotitemController constructor.
     *
     * @param HotitemProductRepository $hotitemProductRepository
     * @param HotitemService $hotitemService
     */
    public function __construct(HotitemProductRepository $hotitemProductRepository, HotitemService $hotitemService)
    {
        $this->hotitemProductRepository = $hotitemProductRepository;
        $this->hotitemService = $hotitemService;
    }

    /**
     * 人気商品一覧.
     *
     * @param Request     $request
     *
     * @return array
     * @Route("/%eccube_admin_route%/plugin/hotitem", name="plugin_hotitem_list")
     * @Template("@Hotitem4/admin/index.twig")
     */
    public function index(Request $request)
    {
        $pagination = $this->hotitemProductRepository->getHotitemList();

        return [
            'pagination' => $pagination,
            'total_item_count' => count($pagination),
        ];
    }

    /**
     * Create & Edit.
     *
     * @param Request     $request
     * @param int         $id
     *
     * @throws \Exception
     *
     * @return array|RedirectResponse
     * @Route("/%eccube_admin_route%/plugin/hotitem/new", name="plugin_hotitem_new")
     * @Route("/%eccube_admin_route%/plugin/hotitem/{id}/edit", name="plugin_hotitem_edit", requirements={"id" = "\d+"})
     * @Template("@Hotitem4/admin/regist.twig")
     */
    public function edit(Request $request, $id = null)
    {
        /* @var HotitemProduct $Hotitem */
        $Hotitem = null;
        $Product = null;
        if (!is_null($id)) {
            // IDから人気商品情報を取得する
            $Hotitem = $this->hotitemProductRepository->find($id);

            if (!$Hotitem) {
                $this->addError('plugin_hotitem.admin.not_found', 'admin');
                log_info('The hotitem product is not found.', ['Hotitem id' => $id]);

                return $this->redirectToRoute('plugin_hotitem_list');
            }

            $Product = $Hotitem->getProduct();
        }

        // formの作成
        /* @var Form $form */
        $form = $this->formFactory
            ->createBuilder(HotitemProductType::class, $Hotitem)
            ->getForm();

        $form->handleRequest($request);
        $data = $form->getData();
        if ($form->isSubmitted() && $form->isValid()) {
            $service = $this->hotitemService;
            if (is_null($data['id'])) {
                if ($status = $service->createHotitem($data)) {
                    $this->addSuccess('plugin_hotitem.admin.register.success', 'admin');
                    log_info('Add the new hotitem product success.', ['Product id' => $data['Product']->getId()]);
                }
            } else {
                if ($status = $service->updateHotitem($data)) {
                    $this->addSuccess('plugin_hotitem.admin.update.success', 'admin');
                    log_info('Update the hotitem product success.', ['Hotitem id' => $Hotitem->getId(), 'Product id' => $data['Product']->getId()]);
                }
            }

            if (!$status) {
                $this->addError('plugin_hotitem.admin.not_found', 'admin');
                log_info('Failed the hotitem product updating.', ['Product id' => $data['Product']->getId()]);
            }

            return $this->redirectToRoute('plugin_hotitem_list');
        }

        if (!empty($data['Product'])) {
            $Product = $data['Product'];
        }

        $arrProductIdByHotitem = $this->hotitemProductRepository->getHotitemProductIdAll();

        return $this->registerView(
            [
                'form' => $form->createView(),
                'hotitem_products' => json_encode($arrProductIdByHotitem),
                'Product' => $Product,
            ]
        );
    }

    /**
     * 人気商品の削除.
     *
     * @param Request     $request
     * @param HotitemProduct $HotitemProduct
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/%eccube_admin_route%/plugin/hotitem/{id}/delete", name="plugin_hotitem_delete", requirements={"id" = "\d+"}, methods={"DELETE"})
     */
    public function delete(Request $request, HotitemProduct $HotitemProduct)
    {
        // Valid token
        $this->isTokenValid();
        // 人気商品情報を削除する
        if ($this->hotitemProductRepository->deleteHotitem($HotitemProduct)) {
            log_info('The hotitem product delete success!', ['Hotitem id' => $HotitemProduct->getId()]);
            $this->addSuccess('plugin_hotitem.admin.delete.success', 'admin');
        } else {
            $this->addError('plugin_hotitem.admin.not_found', 'admin');
            log_info('The hotitem product is not found.', ['Hotitem id' => $HotitemProduct->getId()]);
        }

        return $this->redirectToRoute('plugin_hotitem_list');
    }

    /**
     * Move rank with ajax.
     *
     * @param Request     $request
     *
     * @throws \Exception
     *
     * @return Response
     *
     * @Route("/%eccube_admin_route%/plugin/hotitem/sort_no/move", name="plugin_hotitem_rank_move")
     */
    public function moveRank(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $arrRank = $request->request->all();
            $arrRankMoved = $this->hotitemProductRepository->moveHotitemRank($arrRank);
            log_info('Hotitem move rank', $arrRankMoved);
        }

        return new Response('OK');
    }

    /**
     * 編集画面用のrender.
     *
     * @param array       $parameters
     *
     * @return array
     */
    protected function registerView($parameters = [])
    {
        // 商品検索フォーム
        $searchProductModalForm = $this->formFactory->createBuilder(SearchProductType::class)->getForm();
        $viewParameters = [
            'searchProductModalForm' => $searchProductModalForm->createView(),
        ];
        $viewParameters += $parameters;

        return $viewParameters;
    }
}
