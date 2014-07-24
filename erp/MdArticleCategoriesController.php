<?php

namespace Admin\MasterDataBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Form\FormError;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Admin\UserManagementBundle\Entity\UmAudit;
use Admin\UserManagementBundle\Entity\UmModules;
use Admin\MasterDataBundle\Entity\MdArticleCategories;
use Admin\MasterDataBundle\Entity\MdArticles;

use Admin\MasterDataBundle\Form\MdArticleCategoriesType;
use Xpert\SetupBundle\Service\Translate\Translate;
use Admin\MasterDataBundle\Controller\MainController;

/**
 * MdArticleCategories controller.
 *
 */
class MdArticleCategoriesController extends MainController
{

    public function __construct(){
        parent::__construct();
    }


    /**
     * Lists all MdArticleCategories entities.
     *
     */
    public function indexAction()
    {
        $ext_array = array();
        $headers   = array();
        $hasArticles = false;

        $em = $this->getDoctrine()->getManager();

        $filter_model = new MdArticleCategories();
        $form   = $this->createCreateForm($filter_model);
        $form->add('status', 'choice', array('choices'   => array(0=>'inactiv', 1=>'activ'), 'empty_value' => '==========='));
 
        $this->getCurrentNode();
        $acces_type = $this->current_node['access_type'];
        if($acces_type == 0)
           return $this->redirect_page();

        $url = $this->generateUrl($this->container->get('request')->get('_route'), array(), false);
        $absolute_url   = $this->generateUrl($this->container->get('request')->get('_route'), array(), true);

        $ext_headers['default_sort'] ='a.left_id';
        $ext_headers['default_dir']  ='ASC';
        $ext_headers['grid_url']     = $url;
        $ext_headers['access_type']  = $acces_type;
        $ext_headers['pageSize']     = 50;
        
        $ext_headers['action_col']    = array();
        $absolute_url   = $this->generateUrl($this->container->get('request')->get('_route'), array(), true);

        if ($this->current_node['access_type'] == 2 || $this->current_node['access_type'] == 1 ) {
            $ext_headers['dbl_click_edit']  = array( 'action_url' =>$url, 'action_name' =>'edit');
        }


        // key : edit/ delete - if other key , you must have image like: in img src="'+base_url+'../bundles/xpertsetup/images/edit.png 
        if($this->current_node['access_type'] == 2){
            //$ext_headers['action_col']['edit']        = array( 'action_url' =>$url.'{1}/edit', 'set_page_extra_param'=>'a=1&b=2');
            $ext_headers['action_col']['edit']          = array( 'action_url' =>$url.'{1}/edit');
            
          //$ext_headers['action_col']['macheta_csv']   = array( 'action_url' =>$url.'{1}/edit');
            //$ext_headers['action_col']['delete2']    = array( 'action_url'=>$url.'{1}/delete' );
            //$ext_headers['action_col']['export']      = array( 'onClickAction'=> " window.open('".$absolute_url."{1}/export_csv', '_blank'); " );
        }
        $ext_headers['action_col']['macheta_csv']           = array( 'onClickAction'=> " window.open('".$absolute_url."{1}/export_macheta', '_blank'); " );
        /*if($this->current_node['access_type'] > 0){
            $ext_headers['action_col']['export']    = array( 'onClickAction'=> " window.open('".$absolute_url."{1}/export_csv', '_blank'); " );
        } */

        $ext_headers['model']  = array(
                        'id', 'name', 'companyName', 'url', 'level', 'margin','parent', 'left_id', 'articole_no', 'action'
                        //array('name'=>'insert_date', 'mapping'=> 'insert_date', 'type'=> 'date', 'dateFormat'=> 'timestamp') // for demo
                        //
                );

        $ext_headers['grid_format'][] = array( 'text'=>'Id', 'dataIndex'=>'id', 'width'=>50, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('category'), 'dataIndex'=>'name', 'width'=>300, 'sortable'=>true);
        //$ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('description'), 'dataIndex'=>'description', 'width'=>200, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('company_col_name'), 'dataIndex'=>'companyName', 'width'=>200, 'sortable'=>true);
        //$ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('position_no'), 'dataIndex'=>'position_no', 'width'=>100, 'sortable'=>true);
        //$ext_headers['grid_format'][] = array( 'text'=>'Status', 'dataIndex'=>'status', 'width'=>80, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=>'Level', 'dataIndex'=>'level', 'width'=>80, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('margin'), 'dataIndex'=>'margin', 'width'=>80, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('action'), 'dataIndex'=>'action', 'width'=>200, 'sortable'=>false, 'align'=> 'left', 'flex'=> 1, 'renderer'=> 'renderAction');

        if ($this->getRequest()->get('is_xtjs_request') == 1){
            $req_info = $this->getRequest()->request->all();
            $req_info['cid'] = $this->getRequest()->getSession()->get('selected_company');

            //$search_type   - end_with, start_with, full  => %like, ; like%; %like%
            //  other : =, <; <=; >=, BETWEEN, between_limit 
            $filters_map = array();
            $map_results = array();

            $filters_map['a.id']            = array();
            $filters_map['a.parent']        = array('search_type'=>'get_children', 'in_select'=> false, 'table_prefix' => 'a');
            $filters_map['a.level']         = array('search_type'=>'=');
            $filters_map['a.description']   = array();
            $filters_map['margin']          = array('in_select'=> false);
            //$filters_map['a.position_no']   = array();
            $filters_map['a.name']          = array('search_type'=>'full');
            $filters_map['b.name']          = array('alias'=>'companyName');
            $filters_map['b.id_company']    = array('search_type'=>'=', 'in_select'=> false);
            //$filters_map['a.status']        = array('search_type'=>'=', 'in_select'=> false);
            $filters_map['a.left_id']       = array('in_select'=> false);

            return new JsonResponse($this->getDataGrid($req_info, $ext_headers['model'], 'AdminMasterDataBundle:MdArticleCategories', $form->getName(), $filters_map));
            exit();

        }

        return  $this->display_page('AdminMasterDataBundle:MdArticleCategories:index.html.twig', array('ext_headers'=>$ext_headers, 'form'   => $form->createView() ));
    }
    /**
     * Creates a new MdArticleCategories entity.
     *
     */
    public function createAction(Request $request)
    {
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2)
        return $this->redirect_page();

        $em = $this->getDoctrine()->getManager();

        $reqParams = $request->request->all();

        $conn = $em->getConnection();
        $conn->beginTransaction();
        try {
            $entity = new MdArticleCategories();
            $form = $this->createCreateForm($entity);
            $form->handleRequest($request);

            $create_var = array();

            /* add attributes */
            if (isset($reqParams['attr'])) {
                $attributes = $reqParams['attr'];
                if(isset($reqParams['create_var']))
                    $create_var = $reqParams['create_var'];
                $attr_unique = array_count_values($attributes);
                $attributes_combo = $em->getRepository('AdminMasterDataBundle:MdArticleAttributes')
                    ->getAttributes($this->getRequest()->getSession()->get('selected_company'), strtoupper($this->getRequest()->getSession()->get('_locale')));
                    
                foreach ($attr_unique as $key => $value) {
                    if ($value > 1) {
                        //in variable $attributes_combo[$key] is the name
                        $error = new formError('valid_attribute_unique');
                        $form->get('name')->addError($error);
                    }
                }
            }

            if($form->get('parent')->getData()==null) {
                $category_has_articles = false;
                if (strlen($form->get('shortName')->getData()) == 0) {
                    $error = new formError('valid_categ_shortname');
                    $form->get('name')->addError($error);
                }
            } else {
                //$category_has_articles = $em->getRepository('AdminMasterDataBundle:MdArticles')->findBy(array('category'=>$form->get('parent')->getData(), 'companyId'=>$this->getRequest()->getSession()->get('selected_company')));
                $category_has_articles = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->categoryHasArticleVariants($this->getRequest()->getSession()->get('selected_company'), $form->get('parent')->getData());
            }
                
            if($this->getRequest()->get($form->getName())) {
                    if ($form->isValid() && empty($category_has_articles)) {
                        $parent = $form['parent']->getData();

                        if($parent == ''){
                            $sql = "SELECT MAX(right_id) AS right_id, 0 AS level FROM md_article_categories WHERE company_id=:cid";
                            $parent = $conn->executeQuery($sql, array('cid'=>$this->getRequest()->getSession()->get('selected_company')))->fetchAll();
                            $max_right = $parent[0]['right_id'] == ''? 0 : $parent[0]['right_id']+1;
                        }else{
                            $sql = "SELECT right_id, level+1 as level FROM md_article_categories WHERE id=:param AND company_id=:cid";
                            $params = array( 'param' => $parent, 'cid'=>$this->getRequest()->getSession()->get('selected_company') );
                            $parent = $conn->executeQuery($sql, $params)->fetchAll();
                            $max_right = $parent[0]['right_id'] == ''? 0 : $parent[0]['right_id'];
                        }
                        $level = $parent[0]['level'];

                        $entity->setLeftId($max_right);
                        $entity->setRightId($max_right+1);
                        $entity->setStatus(1);
                        $entity->setLevel($level);
                        if($level > 0) $entity->setShortName(null);
                        $entity->setUrl('test');
                        $entity->setPositionNo('');
                        $session = $this->getRequest()->getSession();
                        $entity->setCompanyId( $session->get('selected_company') );

                        if($max_right > 0){
                            $update_other_nodes = "UPDATE md_article_categories SET right_id = right_id+2 WHERE right_id>=:param AND company_id=:cid";
                            $params = array( 'param' => $max_right, 'cid'=>$this->getRequest()->getSession()->get('selected_company') );
                            $parent = $conn->executeQuery($update_other_nodes, $params);
                            $update_other_nodes = "UPDATE md_article_categories SET left_id = left_id+2 WHERE left_id>=:param AND company_id=:cid";
                            $params = array( 'param' => $max_right, 'cid'=>$this->getRequest()->getSession()->get('selected_company') );
                            $parent = $conn->executeQuery($update_other_nodes, $params);
                        }

                        $em->persist($entity);
                        $em->flush();

                        if (isset($reqParams['form_categories']['hasAttributes']) && isset($reqParams['attr'])) {
                            $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->insertCategAttributes($attributes, $entity->getId(), $create_var);
                        }

                        /* for audit */
                        $this->getCurrentNode();
                        $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find($this->current_node['id_module']);
                        $audit = $this->getAudit('INSERT', 'Category with id='.$entity->getId().' created.', $module);
                        $em->persist($audit);
                        /***********/
                        $em->flush();
                        $conn->commit();
                        
                        $this->get('session')->getFlashBag()->add(
                            'notice',
                            $this->translateWord('category_add_ok')
                        );

                        return $this->js_redirect($this->generateUrl('masterdata_categories_edit', array('id' => $entity->getId())));
                        //return $this->redirect($this->generateUrl('masterdata_categories'));
                    }else{
                                   //echo "<pre>";        \Doctrine\Common\Util\Debug::dump($category_has_articles); die('aaa');
                        if(!empty($category_has_articles)){
                            $error = new formerror("valid_md_category_has_articles");
                            $form->get('name')->addError($error);
                        }
                    }
                }
            } catch (\Exception $e) {
                $conn->rollback();
                    echo 'Error: ',  $e->getMessage(), "\n";
                exit;
            }

        return  $this->display_page('AdminMasterDataBundle:MdArticleCategories:new.html.twig', array(
            'entity' => $entity,
            'attributes' => $attributes,
            'create_var' => $create_var,
            'attr_combo' => $attributes_combo,
            'form'   => $form->createView()
        ));
    }

    /**
    * Creates a form to create a MdArticleCategories entity.
    *
    * @param MdArticleCategories $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(MdArticleCategories $entity)
    {
        $form = $this->createForm(new MdArticleCategoriesType(), $entity, array(
            'action' => $this->generateUrl('masterdata_categories_create'),
            'method' => 'POST',
        ));

        $em = $this->getDoctrine()->getManager();
        $connection = $em->getConnection();

        //$attributes = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')
        $hierarchy = array();
        $result = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->findBy(array('status'=>1, 'companyId'=>$this->getRequest()->getSession()->get('selected_company')), array('leftId'=>'ASC'));
        foreach($result as $k=>$v) $hierarchy[$v->getId()] = str_repeat(html_entity_decode('- ', ENT_QUOTES, 'UTF-8'), $v->getLevel()*3).$v->getName();
        $form->add('parent', 'choice', array('choices' => $hierarchy, 'mapped' => false, 'empty_value' => ''));
        $form->add('hasAttributes', 'checkbox', array('mapped'=>false));
        
        $attributes = $em->getRepository('AdminMasterDataBundle:MdArticleAttributes')
                ->getAttributes($this->getRequest()->getSession()->get('selected_company'), strtoupper($this->getRequest()->getSession()->get('_locale')), 1);

        $form->add('cmbAttr', 'choice', array(
            'mapped' => false,
            'choices'=> $attributes
            ));

        $form->add('create', 'button', array(
                'label' => $this->translateWord('save'),
                'attr' => array('class'     => 'btn btn-primary',
                                'style'     => 'float:left; margin:0 auto',
                                'onclick'   => "javascript:submit_frm(this, '', {is_button:1})")
            ));

        return $form;
    }

    /**
     * Displays a form to create a new MdArticleCategories entity.
     *
     */
    public function newAction()
    {
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2)
           return $this->redirect_page();

       $em = $this->getDoctrine()->getManager();

        $entity = new MdArticleCategories();
        $form   = $this->createCreateForm($entity);

        $attr_qtyinfo = $em->getRepository('AdminMasterDataBundle:MdArticleAttributes')->getAttributesQtyInfluence($this->getRequest()->getSession()->get('selected_company'), 1);

        return $this->display_page('AdminMasterDataBundle:MdArticleCategories:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'attr_qtyinfo' => $attr_qtyinfo
        ));
    }

    /**
     * Displays a form to edit an existing MdArticleCategories entity.
     *
     */
    public function editAction($id, Request $request)
    {
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2)
           return $this->redirect_page();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->findOneBy(array( 'id'=>$id, 'companyId'=>$this->getRequest()->getSession()->get('selected_company') ));
        $is_parent = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->isParent(array('id'=>$id, 'companyId'=>$this->getRequest()->getSession()->get('selected_company')));

        if (!$entity) {
            return $this->js_redirect($this->generateUrl('masterdata_categories'));
        }

        $editForm = $this->createEditForm($entity);
        //$category_has_articles =  $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->categoryHasArticleVariants($this->getRequest()->getSession()->get('selected_company'), $id);

        $attributes = array();
//        $attributes_raw = $em->getRepository('AdminMasterDataBundle:MdCategoryAttributes')->findBy(array('idCategory' => $id, 'active' => 1));
//        foreach ($attributes_raw as $key => $attr_item) {
//            $attributes[] = $attr_item->getIdAttribute();
//        }
        $attributes = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getAssociatedArticles(array('category_id' => $id, 'company_id' => $this->getRequest()->getSession()->get('selected_company')));
        $attr_qtyinfo = $em->getRepository('AdminMasterDataBundle:MdArticleAttributes')->getAttributesQtyInfluence($this->getRequest()->getSession()->get('selected_company'), 1);

        $create_var = array();

        // foreach ($attributes as $key => $value) {
        //     if(isset($category_has_articles[$key])) 
        //         $attributes[$key]['has_articles'] = $category_has_articles[$key]['has_articles'];
        //     else
        //         $attributes[$key]['has_articles'] = 0;
        // }

        $sql = "SELECT id_attribute,create_var FROM md_category_attributes WHERE id_category='".$id."'";
        $q = $em->getConnection()->prepare($sql);
        $q->execute();
        $results = $q->fetchAll();
        foreach ($results as $key => $result) {
                $create_var[$result['id_attribute']] = $result['create_var'];
        }

        $attributes_combo = $em->getRepository('AdminMasterDataBundle:MdArticleAttributes')
                ->getAttributes($this->getRequest()->getSession()->get('selected_company'), strtoupper($this->getRequest()->getSession()->get('_locale')), 1);

        return $this->display_page('AdminMasterDataBundle:MdArticleCategories:edit.html.twig', array(
            'entity'      => $entity,
            'attributes'  => $attributes,
            'attr_combo'  => $attributes_combo,
            'attr_qtyinfo' => $attr_qtyinfo,
            'create_var'  => $create_var,
            'edit_form'   => $editForm->createView(),
            'is_parent'   => $is_parent
        ));
    }

    /**
    * Creates a form to edit a MdArticleCategories entity.
    *
    * @param MdArticleCategories $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MdArticleCategories $entity)
    {
        $form = $this->createForm(new MdArticleCategoriesType(), $entity, array(
            'action' => $this->generateUrl('masterdata_categories_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $em = $this->getDoctrine()->getManager();
        $conn = $em->getConnection();

        $sql = "SELECT id, name, level FROM md_article_categories WHERE status=1 AND right_id > :rightid AND left_id < :leftid AND company_id = :cid ORDER BY left_id";
        $params = array( 'rightid'=>$entity->getRightId(), 'leftid'=>$entity->getLeftId(), 'cid'=>$this->getRequest()->getSession()->get('selected_company') );
        $result = $conn->executeQuery($sql, $params)->fetchAll();
        $hierarchy = array();
        foreach($result as $k=>$v) $hierarchy[$v['id']] = str_repeat(html_entity_decode('- ', ENT_QUOTES, 'UTF-8'), $v['level']*3).$v['name'];
        $form->add('parent', 'choice', array('choices' => $hierarchy, 'mapped' => false, 'attr' => array('style' => 'min-width:150px')));
        $form->add('hasAttributes', 'checkbox', array('mapped'=>false));
        
        $attributes = $em->getRepository('AdminMasterDataBundle:MdArticleAttributes')
                ->getAttributes($this->getRequest()->getSession()->get('selected_company'), strtoupper($this->getRequest()->getSession()->get('_locale')), 1);
        $form->add('cmbAttr', 'choice', array(
            'mapped' => false,
            'choices'=> $attributes
            ));
        $form->add('update', 'button', array(
                'label' => $this->translateWord('update_btn'),
                'attr' => array('class' => 'btn btn-primary',
                                'style' => 'float:left; margin:0 auto',
                                'onclick' => 'javascript:submit_frm(this)')
            ));

        return $form;
    }
    /**
     * Edits an existing MdArticleCategories entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2)
           return $this->redirect_page();

        $em = $this->getDoctrine()->getManager();
        $conn = $em->getConnection();

        $entity = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->findOneBy(array( 'id'=>$id, 'companyId'=>$this->getRequest()->getSession()->get('selected_company') ));

        $old_name = $entity->getName();
        $old_level = $entity->getLevel();

        if (!$entity) {
            return $this->js_redirect($this->generateUrl('masterdata_categories'));
        }
        $entity_old = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getAsArray($id);
        $entity2 = clone $entity;

        $editForm = $this->createEditForm($entity2);
        $editForm->handleRequest($request);

        $reqParams = $request->request->all();

        $to_insert_attributes = array();
        $create_var = array();
        $insert_create_var = array();
        $category_has_articles = array();

        $previous_attributes = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getAssociatedArticles(array('category_id' => $id, 'company_id' => $this->getRequest()->getSession()->get('selected_company')));
        $category_has_articles =  $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->categoryHasArticleVariants($this->getRequest()->getSession()->get('selected_company'), $id);
        
        $conn = $em->getConnection();
        $conn->beginTransaction();

        try{
            if (isset($reqParams['attr'])) {
                $attributes_x = $reqParams['attr'];
                if(isset($reqParams['create_var']))
                    $create_var = $reqParams['create_var'];
                $attributes = array();
                $attr_unique = array_count_values($attributes_x);
                foreach ($attr_unique as $key => $value) {
                    if ($value > 1) {
                        //in variable $attributes_combo[$key] is the name
                        $error = new formError('valid_attribute_unique');
                        $editForm->get('name')->addError($error);
                    }
                }
                if(!empty($attributes_x)) foreach($attributes_x as $k=>$v) {
                    $to_insert_attributes[$v] = $v;
                    $insert_create_var[$v] = (isset($create_var[$v])? $create_var[$v] : 0 );
                    $attributes[$v]['id_attr'] = $v;
                    if(isset($previous_attributes[$v])) $attributes[$v]['has_articles'] = $previous_attributes[$v]['has_articles'];
                }
            }else{
                $attributes = $previous_attributes;
            }

            /* add attributes */
            $attributes_combo = $em->getRepository('AdminMasterDataBundle:MdArticleAttributes')
                    ->getAttributes($this->getRequest()->getSession()->get('selected_company'), strtoupper($this->getRequest()->getSession()->get('_locale')));

            $is_parent = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->isParent(array('id'=>$id, 'companyId'=>$this->getRequest()->getSession()->get('selected_company')));

            if(!$is_parent) {
                if($editForm->get('margin')->getData()==null)
                {
                    $error = new formError('valid_md_margin');
                    $editForm->get('margin')->addError($error);
                }
            }
            if($this->getRequest()->get($editForm->getName())) 
            {
                if ($editForm->isValid()) {

                    if($old_name != $editForm['name']->getData())
                    {
                        $update_sent_to_magento = "UPDATE md_article_categories SET category_was_changed = 1 WHERE id=:id";
                        $params = array( 'id' => $id );
                        $parent = $conn->executeQuery($update_sent_to_magento, $params);
                    }

                    $entity->setName($editForm['name']->getData());
                    $entity->setDescription($editForm['description']->getData());
                    $entity->setStatus($editForm['status']->getData());
                    $entity->setPositionNo($editForm['positionNo']->getData());
                    $entity->setMargin($editForm['margin']->getData());
                    $entity->setUrl('test');
                    if($entity->getLevel() > 0) $entity->setShortName(null);
                    else $entity->setShortName($editForm['shortName']->getData());
                    //$entity->setCompanyId( $this->getRequest()->getSession()->get('selected_company') );

                    $em->persist($entity);
                    $em->flush();

                    if (isset($reqParams['form_categories']['hasAttributes']) && isset($reqParams['attr'])) {
                        if(empty($previous_attributes)) 
                            $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->insertCategAttributes($to_insert_attributes, $id, $insert_create_var);
                        else{
                            $to_delete_attributes = array();

                            foreach($previous_attributes as $k=>$v){
                                if(!isset($to_insert_attributes[$k]) && $v['has_articles'] == 0){
                                    $to_delete_attributes[$k] = $k;
                                }
                            }
                            foreach($to_insert_attributes as $k=>$v){
                                if(isset($previous_attributes[$k])) unset($to_insert_attributes[$k]);
                            }

                            if(!empty($to_delete_attributes)) $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->deleteCategoryAttributes($to_delete_attributes, $id);
                            if(!empty($to_insert_attributes)) $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->insertCategAttributes($to_insert_attributes, $id, $insert_create_var);

                            foreach ($insert_create_var as $key => $value) {
                                $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->updateAttribute($id, $key, $value);
                            }
                        }
        //                print_r($previous_attributes);
        //                echo "VS";
        //                print_r($attributes);
        //                die('daa');
                        //$em->getRepository('AdminMasterDataBundle:MdArticleCategories')->deleteCategAttributes($id);
                        
                    } else {
                        $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->deleteCategAttributes($id);
                    }
                    $em->flush();

                    /* for audit */
                    $this->getCurrentNode();
                    $entity_new = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getAsArray($id);
                    $updates = $this->getDifferences($entity_old, $entity_new);
                    $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find($this->current_node['id_module']);
                    $audit = $this->getAudit('UPDATE', 'Category with id='.$id.' updated. '.$updates, $module);
                    $em->persist($audit);
                    /***********/
                    $em->flush();
                    $conn->commit();

                    $this->get('session')->getFlashBag()->add('notice', $this->translateWord('category_edit_ok'));
                    return $this->js_redirect($this->generateUrl('masterdata_categories_edit', array('id' => $id)));
                }
            }
        } catch (\Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";
            $conn->rollback();
            exit;
        }

        return $this->display_page('AdminMasterDataBundle:MdArticleCategories:edit.html.twig', array(
            'entity'      => $entity,
            'attributes'  => $attributes,
            'attr_combo'  => $attributes_combo,
            'create_var'  => $create_var,
            'edit_form'   => $editForm->createView(),
            'is_parent'   => $is_parent
        ));
    }
    /**
     * Deletes a MdArticleCategories entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {

        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2)
        return $this->redirect_page();

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->find($id);

        if (!$entity) {
            return $this->js_redirect($this->generateUrl('masterdata_categories'));
        }

        $hasArticles = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->categoryHasArticles($this->getRequest()->getSession()->get('selected_company'), $id);

        if(!$hasArticles)
        {
            $entity->setStatus(0);
            $em->persist($entity);
            /* for audit */
            $this->getCurrentNode();
            $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find($this->current_node['id_module']);
            $audit = $this->getAudit('DELETE', 'Category with id='.$id.' deleted.', $module);
            $em->persist($audit);
            /***********/
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', $this->translateWord('md_category_del_ok'));
        }
        else
        {
            $this->get('session')->getFlashBag()->add('notice', "The Category cannot be deleted because it contains articles");
        }

        return new JsonResponse(1);
        exit();
    }

    public function export_machetaAction(Request $request, $id) {

        $em         = $this->getDoctrine()->getManager();
        $cat_name = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->findById($id);

        $params = array('category' => $id,'lang_id'=>strtoupper($this->getRequest()->getSession()->get('_locale')), 'comp_id'=>$this->getRequest()->getSession()->get('selected_company'));

        $options = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getCategoryAttrCsv($params);

        $csv_headers = array();
        /*$article_columns = array('tip_configurare', 'denumire_articol', 'denumire_la_furnizor', 'cod_articol_la_furnizor', 'furnizor', 'ean', 'descriere_buyer'
            , 'descriere_marketing', 'brand', 'tara_brand', 'cantitate_minima', 'pret_cumparare_lista', 'moneda_cumparare', 'discount_furnizor%'
            ,'pret_achizitie_furnizor', 'pret_achizitie_furnizor_ron_cu_discount_NOTVA', 'marja_categorie', 'cota_tva', 'flash', 'rrp', 'discount_flash_vs_rrp%', 'meta_title', 'meta_cuvinte_cheie', 'meta_descriere','categorie','subcategorie','sub_subcategorie'
        );*/
        $article_columns = array('tip_configurare', 'denumire_articol', 'denumire_la_furnizor', 'cod_articol_la_furnizor', 'furnizor', 'ean', 'descriere_buyer'
            , 'descriere_marketing', 'brand', 'tara_brand', 'descr_succinta','cantitate_minima', 'moneda_cumparare'
            , 'pret_achizitie_furnizor', 'cota_tva', 'flash', 'rrp', 'meta_title', 'meta_cuvinte_cheie', 'meta_descriere','categorie','subcategorie','sub_subcategorie', 'food_nonfood'
        );

        // add static attr
        if(isset($options['static'])){
            foreach ($options['static'] as $key => $value) {
                if(strlen(trim($value['attribute_code'])) > 0)
                    if(strlen(trim($value['attr_um'])) > 0 )
                        $article_columns[] = $value['attribute_code'].'_'.$value['attr_um'];
                    else
                        $article_columns[] = $value['attribute_code'];
            }
        }

        // add dynamic attr
        if(isset($options['qty_infl'])){
            foreach ($options['qty_infl'] as $key => $value) {
                if(strlen(trim($value['attribute_code'])) > 0)
                    if(strlen(trim($value['attr_um'])) > 0 )
                        $article_columns[] = $value['attribute_code'].'_'.$value['attr_um'];
                    else
                        $article_columns[] = $value['attribute_code'];
            }
        }


        /* for SOAP
        foreach ($leafNodes as $node_id => $node) {

            $categoryName = $node['name'];
            $parents = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getParents($node);

            foreach ($parents as $parent) {
                $categoryName = $categoryName . ' / ' . $parent['name'];
            }

            $articles = $em->getRepository('AdminMasterDataBundle:MdArticles')->getExportedByCategory($node_id, $categoryName, $companyId);

            foreach ($articles as $article) {
                $final_data[] = $article;
            }
        }
        */

        $format     = 'csv';
        $path       = $this->base_url.'uploads/temp_csv/';
        $cat_name   = current($cat_name)->getName();
        $cat_name   = preg_replace('/[^a-zA-Z0-9-_\.]/','_', $cat_name);

        $filename = $cat_name."_".date("Y_m_d_His").".csv";

        $fp = fopen($path.$filename, 'w');
        fputcsv($fp, $article_columns);
        fclose($fp);


        //$path = $this->get('kernel')->getRootDir(). "/../../htdocs/serp/temp_csv/"; 
        $file = $path.$filename; // Path to the file on the server
        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', 'text/csv');
      // Give the file a name:
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    public function getMarginAction(){
        if($this->getRequest()->get('cid')=='') die("error");
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->findOneBy(array('id'=>$this->getRequest()->get('cid'), 'companyId'=>$this->getRequest()->getSession()->get('selected_company')));
        $short_name = '';
        if($entity->getLevel() == 0){
            $short_name = $entity->getShortName() == null? '---':$entity->getShortName();
        }
        $x = array('margin'=>$entity->getMargin(), 'short_name'=>$short_name, 'transport_margin'=>$entity->getTransportMargin(), 'level' => $entity->getLevel());
        $tpl = '';
        
        $params = array('category' => $this->getRequest()->get('cid'),'lang_id'=>strtoupper($this->getRequest()->getSession()->get('_locale')), 'comp_id'=>$this->getRequest()->getSession()->get('selected_company'));
        $options = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getCategoryAttributes($params);//$this->getCategoryAttributesTemplate($this->getRequest()->get('cid'));

        $tpl = '';
        if(isset($options['static']))
            $tpl .= $this->renderView('AdminMasterDataBundle:MdArticleCategories:options_static.html.twig', array('options' => $options['static']));
        if(isset($options['qty_infl']))
            $tpl .= $this->renderView('AdminMasterDataBundle:MdArticleCategories:options_qty.html.twig', array('options' => $options['qty_infl']));

        $x['options_html'] = $tpl;

        echo json_encode($x);
        //echo $entity->getMargin();
        exit;
    }
}