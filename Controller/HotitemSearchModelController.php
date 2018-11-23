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

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Controller\AbstractController;
use Eccube\Repository\TagRepository;
use Eccube\Repository\ProductRepository;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Repository\ProductTagRepository;
use Eccube\Form\Type\Admin\SearchProductType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HotitemSearchModelController.
 */
class HotitemSearchModelController extends AbstractController
{
    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * HotitemSearchModelController constructor.
     *
     * @param TagRepository $tagRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(TagRepository $tagRepository, ProductRepository $productRepository)
    {
        $this->tagRepository = $tagRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * 商品検索画面を表示する.
     *
     * @param Request     $request
     * @param int         $page_no
     *
     * @return array
     * @Route("/%eccube_admin_route%/plugin/hotitem/search/product", name="plugin_hotitem_search_product")
     * @Route("/%eccube_admin_route%/plugin/hotitem/search/product/page/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_hotitem_search_product_page")
     * @Template("@Hotitem4/admin/search_product.twig")
     */
    public function searchProduct(Request $request, $page_no = null, Paginator $paginator)
    {
        if (!$request->isXmlHttpRequest()) {
            return [];
        }

        log_debug('Search product start.');

        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $session = $this->session;
           if ('POST' === $request->getMethod()) {
            $page_no = 1;
            $searchData = [
                'name' => trim($request->get('id')),
            ];
            if ($tagId = $request->get('Tag')) {
                $searchData['Tag'] = $tagId;
            }
            
            $session->set('eccube.plugin.hotitem.product.search', $searchData);
            $session->set('eccube.plugin.hotitem.product.search.page_no', $page_no);
        } else {
            $searchData = (array) $session->get('eccube.plugin.hotitem.product.search');
            if (is_null($page_no)) {
                $page_no = intval($session->get('eccube.plugin.hotitem.product.search.page_no'));
            } else {
                $session->set('eccube.plugin.hotitem.product.search.page_no', $page_no);
            }
        }

        //set parameter
        $productname['id'] = $searchData['name'];
        $bigtag = array();
        
        //fliter Tag array
        $qb = $this->productRepository->getQueryBuilderBySearchDataForAdmin($productname);
        if(!empty($searchData['Tag'])){
            foreach ($searchData['Tag'] as $tagname => $tagid) {
                array_push($bigtag,$this->tagRepository->find($tagid['value']));
            }
            $qb->innerJoin('p.ProductTag', 'ptag')
                ->innerJoin('ptag.Tag', 'tag')
                ->andWhere($qb->expr()->in('ptag.Tag', ':Tag'))
                ->setParameter(':Tag',$bigtag);
        }

        /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount,
            ['wrap-queries' => true]
        );

        /** @var ArrayCollection */
        $arrProduct = $pagination->getItems();

        log_debug('Search product finish.');
        if (count($arrProduct) == 0) {
            log_debug('Search product not found.');
        }

        return [
            'pagination' => $pagination,
        ];
    }
}
