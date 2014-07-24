<?php

namespace Admin\UserManagementBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Admin\UserManagementBundle\Entity\UmUsers;
use Admin\UserManagementBundle\Entity\UmUsersHist;
use Admin\UserManagementBundle\Entity\UmModules;
use Admin\UserManagementBundle\Entity\UmUserRoles;
use Admin\UserManagementBundle\Entity\NomCompanies;
use Admin\UserManagementBundle\Form\UmUsersType;
use Xpert\SetupBundle\Service\Translate\Translate;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * UmUsers controller.
 *
 */
class UmUsersController extends MainController
{

    /**
     * Lists all UmUsers entities.
     *
     */
    public function indexAction()
    {

        $ext_array = array();
        $headers   = array();

        $em = $this->getDoctrine()->getManager();
        $filter_model   = new UmUsers();
        $form           = $this->createCreateForm($filter_model);
        $form->add('active', 'choice', array(
                'choices' => array(1=> 'YES', 0=>'NO'),
                'data' => 1,
                'empty_value' => '---'));
 
        $this->getCurrentNode();
        $acces_type = $this->current_node['access_type'];
        if($acces_type == 0)
           return $this->redirect_page();

        $url = $this->generateUrl($this->container->get('request')->get('_route'), array(), false);
        $absolute_url   = $this->generateUrl($this->container->get('request')->get('_route'), array(), true);

        $ext_headers['default_sort'] ='id_user';
        $ext_headers['default_dir']  ='DESC';
        $ext_headers['grid_url']     = $url;
        $ext_headers['access_type']  = $acces_type;
        $ext_headers['pageSize']    = 50;

        $ext_headers['action_col']    = array();

        // key : edit/ delete - if other key , you must have image like: in img src="'+base_url+'../bundles/xpertsetup/images/edit.png 
        if($this->current_node['access_type'] == 2){
            //$ext_headers['action_col']['edit']      = array( 'action_url' =>$url.'{1}/edit', 'set_page_extra_param'=>'a=1&b=2');
            $ext_headers['action_col']['edit']      = array( 'action_url' =>$url.'{1}/edit');
            $ext_headers['action_col']['delete2']    = array( 'action_url'=>$url.'{1}/delete' );
            //$ext_headers['action_col']['export']    = array( 'onClickAction'=> " window.open('".$absolute_url."{1}/export_csv', '_blank'); " );
        }

        /*
        $ext_headers['model']  = array(
                        'id_user', 'name', 'email', 'roles', 'active','last_login', 'registration_date', 'activation_date', 'action'
                        //array('name'=>'insert_date', 'mapping'=> 'insert_date', 'type'=> 'date', 'dateFormat'=> 'timestamp') // for demo
                );
        */
        $ext_headers['model'][]  = array('name'=>'id_user', 'type'=>'int');
        $ext_headers['model'][]  = array('name'=>'name');
        $ext_headers['model'][]  = array('name'=>'email');
        $ext_headers['model'][]  = array('name'=>'roles');
        $ext_headers['model'][]  = array('name'=>'active');
        $ext_headers['model'][]  = array('name'=>'last_login', 'type'=> 'date');
        $ext_headers['model'][]  = array('name'=>'registration_date', 'type'=> 'date');
        $ext_headers['model'][]  = array('name'=>'activation_date', 'type'=> 'date');
        $ext_headers['model'][]  = array('name'=>'action');

        //summaryType: 'average'
        $ext_headers['grid_format'][] = array( 'text'=>'Id', 'dataIndex'=>'id_user', 'width'=>50, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('um_user_title'), 'dataIndex'=>'name', 'width'=>170, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=>'Email', 'dataIndex'=>'email', 'width'=>150, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('roles'), 'dataIndex'=>'roles', 'width'=>200, 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('active'), 'dataIndex'=>'active', 'width'=>70, 'align'=> 'center', 'sortable'=>true);
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('last_login'), 'dataIndex'=>'last_login', 'width'=>130, 'sortable'=>true, 'xtype'=>'datecolumn', 'format'=>'d-m-Y h:m:s');
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('registration_date'), 'dataIndex'=>'registration_date', 'width'=>130, 'sortable'=>true, 'xtype'=>'datecolumn', 'format'=>'d-m-Y h:m:s');
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('activation_date'), 'dataIndex'=>'activation_date', 'width'=>130, 'sortable'=>true, 'xtype'=>'datecolumn', 'format'=>'d-m-Y h:m:s');
        $ext_headers['grid_format'][] = array( 'text'=> $this->translateWord('action'), 'dataIndex'=>'action', 'width'=>150, 'sortable'=>false, 'align'=> 'left', 'flex'=> 1, 'renderer'=> 'renderAction');

        if ($this->getRequest()->get('is_xtjs_request') == 1){

            $req_info = $this->getRequest()->request->all();
            
             //$search_type   - end_with, start_with, full  => %like, ; like%; %like%
            //  other : =, <; <=; >=, BETWEEN, between_limit 
            $filters_map = array();
            $map_results = array();

            $filters_map['a.id_user']           = array();
            $filters_map['a.name']              = array('search_type'=>'full');
            $filters_map['a.email']             = array('search_type'=>'full');
            $filters_map['a.last_login']        = array();
            $filters_map['a.registration_date']  = array();
            $filters_map['a.activation_date']   = array();
            $filters_map['a.active']            = array('search_type'=>'start_with', 'in_select'=> false);

            return new JsonResponse($this->getDataGrid($req_info, $ext_headers['model'], 'AdminUserManagementBundle:UmUsers', $form->getName(), $filters_map));
            exit();
        }

        return  $this->display_page('AdminUserManagementBundle:UmUsers:index.html.twig', array('ext_headers'=>$ext_headers, 'form'   => $form->createView() ));
    }
    /**
     * Creates a new UmUsers entity.
     *
     */
    public function createAction(Request $request)
    {
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2)
           return $this->redirect_page();

        $userEntity = new UmUsers();
        $em = $this->getDoctrine()->getManager();
        $userEntity->setRegistrationDate(new \DateTime());
        $form = $this->createCreateForm($userEntity);
        $form->handleRequest($request);

        $categories_check = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getCategoriesbyLevel(1, $this->container->get('request')->getSession()->get('selected_company'));
        unset($categories_check[0]);

        $reqParams =  $request->request->all();
        $formValues = array();
        if (isset($reqParams['form_umusers'])) 
            $formValues = $reqParams['form_umusers'];

        $conn = $em->getConnection();
        $conn->beginTransaction();

        try{
            if ($form->isValid()) {

                $activation_code = md5(microtime().time().rand(100000,999999));
                // $temp_pass = sha1($this->generatePassword(10));
                // $userEntity->setPassword($temp_pass);
                $userEntity->setActivationCode($activation_code);
                $userEntity->setLocked(0);
                $userEntity->setPasswordExpired(0);
                $em->persist($userEntity);
                $em->flush();

                if (isset($reqParams['categ_vals'])) {
                    $em->getRepository('AdminUserManagementBundle:UmUsers')->updateUserCategories($reqParams['categ_vals'], $userEntity->getId());
                }

                if (isset($formValues['idRole'])) {
                    $idCompanies = array();
                    if (isset($reqParams['idCompany'])) $idCompanies = $reqParams['idCompany'];
                    if (isset($formValues['idCompany'])) $idCompanies = $formValues['idCompany'];

                    if (count($idCompanies) == 0) {
                        $this->get('session')->getFlashBag()->add('notice', $this->translateWord('error_nocompany_sel'));

                        return  $this->display_page('AdminUserManagementBundle:UmUsers:new.html.twig', array(
                            'entity' => $userEntity,
                            'categories_check' => $categories_check,
                            'form'   => $form->createView(),
                        ));
                    } else {

                        foreach ($idCompanies as $key => $idComp) {
                            $userRole = new UmUserRoles();
                            $role = $em->getRepository('AdminUserManagementBundle:UmRoles')->find($formValues['idRole']);
                            $comp = $em->getRepository('AdminUserManagementBundle:NomCompanies')->find($idComp);
                            $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find(1); /*dummy */
                            //$em->persist($userEntity);

                            $userRole->setIdUser($userEntity);
                            $userRole->setIdRole($role);
                            $userRole->setIdCompany($comp);
                            $userRole->setIdRight($module);

                            $em->persist($userRole);
                        }
                    }

                } elseif (isset($reqParams['roles'])) {
                    $roles = $reqParams['roles'];
                    foreach ($roles as $key => $role) {
                        if (!isset($reqParams['idCompany'][$key])) {
                            $this->get('session')->getFlashBag()->add('notice', $this->translateWord('error_role_nocompany'));

                            return  $this->display_page('AdminUserManagementBundle:UmUsers:new.html.twig', array(
                                'entity' => $userEntity,
                                'categories_check' => $categories_check,
                                'form'   => $form->createView(),
                            ));
                        } 
                    }
                    /* Daca nu avem nicio eroare de validare pentru companii*/
                    if (count($roles) !== count(array_unique($roles))) {
                        $this->get('session')->getFlashBag()->add('notice', $this->translateWord('error_multiple_role'));

                        return  $this->display_page('AdminUserManagementBundle:UmUsers:new.html.twig', array(
                            'entity' => $userEntity,
                            'form'   => $form->createView(),
                        ));
                    }
                    /* Daca nu avem nicio eroare de validare pentru roles*/

                    foreach ($roles as $key => $role) {
                        $idCompanies = $reqParams['idCompany'];

                        $role = $em->getRepository('AdminUserManagementBundle:UmRoles')->find($role);
                        foreach ($idCompanies[$key] as $idComp) {
                            $userRole = new UmUserRoles();
                            $comp = $em->getRepository('AdminUserManagementBundle:NomCompanies')->find($idComp);
                            $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find(1);
                            //$em->persist($userEntity);

                            $userRole->setIdUser($userEntity);
                            $userRole->setIdRole($role);
                            $userRole->setIdCompany($comp);
                            $userRole->setIdRight($module);

                            $em->persist($userRole);
                        }
                    }

                } else {
                    $this->get('session')->getFlashBag()->add('notice', 'A aparut o eroare. Va rugam reincercati!');

                    return  $this->display_page('AdminUserManagementBundle:UmUsers:new.html.twig', array(
                        'entity' => $userEntity,
                        'categories_check' => $categories_check,
                        'form'   => $form->createView(),
                    ));
                }
                $em->flush();
                /**** for audit ******/
                $this->getCurrentNode();
                $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find($this->current_node['id_module']);
                $audit = $this->getAudit('INSERT', 'Account with id='.$userEntity->getId().' created!', $module, $this->get('security.context')->getToken()->getUser());
                $em->persist($audit);
                /*****for email queue***/
                $emails = array('to'=>$userEntity->getEmail(), 'cc'=>'', 'bcc'=>'');
                $link = $this->generateUrl('confirm_account', array('code' => $activation_code), true);
                $body = sprintf($this->translateWord('message_new_account'),$link, $link, $emails['to']);
                /* also sends mail - to be modified */
                $email = $this->getEmailQueue($emails, $this->translateWord('subject_new_account'), $body, 0, 5);
                $em->persist($email);
                /************************/

                $em->flush();
                $em->getRepository('AdminUserManagementBundle:UmUsers')->refreshRightsCompiled($conn, $userEntity->getId());

                $conn->commit();

                $this->get('session')->getFlashBag()->add('notice', $this->translateWord('um_user_add_ok')
                );

                return $this->js_redirect($this->generateUrl('admin_users_edit', array('id' => $userEntity->getId())));
            }
        } catch (\Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";
            $conn->rollback();
            exit;
        }

        return  $this->display_page('AdminUserManagementBundle:UmUsers:new.html.twig', array(
            'entity' => $userEntity,
            'categories_check' => $categories_check,
            'form'   => $form->createView(),
        ));
    }

    /**
    * Creates a form to create a UmUsers entity.
    *
    * @param UmUsers $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(UmUsers $entity)
    {
        $em = $this->getDoctrine()->getManager();
        $roles = $em->getRepository('AdminUserManagementBundle:UmRoles')->findByStatus(1);
        $companies = $em->getRepository('AdminUserManagementBundle:NomCompanies')->findBy(array('status' => 1),array(), 3);

        $rolesCombo = array();
        $compSelect = array();
        foreach ($roles as $key => $role) {
            $rolesCombo[$role->getId()] = $role->getName();
        }
        foreach ($companies as $key => $company) {
            $compSelect[$company->getId()] = $company->getName();
        }

        $form = $this->createForm(new UmUsersType(), $entity, array(
            'action' => $this->generateUrl('admin_users_create'),
            'method' => 'POST',
        ));

        $form->add('create', 'button', array(
                'label' => $this->translateWord('save'),
                'attr' => array('class' => 'btn btn-primary',
                                'style' => 'float:left; margin:0 auto',
                                'onclick' => 'javascript:submit_frm(this)')
            ))
            ->add('idRole', 'choice', array(
                'mapped' => false,
                'choices' => $rolesCombo,
                'data'     => key($rolesCombo),
                'multiple' => false,
                'expanded' => false))
            ->add('idCompany', 'choice', array(
                'mapped' => false,
                'choices' => $compSelect,
                'multiple' => true,
                'expanded' => true))
        ;

        return $form;
    }

    /**
     * Displays a form to create a new UmUsers entity.
     *
     */
    public function newAction()
    {
        $em = $this->getDoctrine()->getManager();
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2) return $this->redirect_page();

        $entity = new UmUsers();
        $entity->setRegistrationDate(new \DateTime());

        $categories_check = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getCategoriesbyLevel(1, $this->container->get('request')->getSession()->get('selected_company'));
        unset($categories_check[0]);

        $form   = $this->createCreateForm($entity);

        return  $this->display_page('AdminUserManagementBundle:UmUsers:new.html.twig', array(
            'entity' => $entity,
            'categories_check' => $categories_check,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing UmUsers entity.
     *
     */
    public function editAction($id)
    {
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2) return $this->redirect_page();

        $show_active = true;
        if ($id == $this->get('security.context')->getToken()->getUser()->getId()){
            $show_active = false; /*current user can't deactive own account */
        }

        $em = $this->getDoctrine()->getManager();
        $userEntity = $em->getRepository('AdminUserManagementBundle:UmUsers')->find($id);

        if (!$userEntity) {
            //throw $this->createNotFoundException('Unable to find UmUsers entity.');
            return $this->js_redirect($this->generateUrl('admin_users'));
        }

        $userRoles = $em->getRepository('AdminUserManagementBundle:UmUserRoles')->findUserRoles($id); //returns array()
        $categories = $em->getRepository('AdminUserManagementBundle:UmUsers')->getUserCategories($id);
        $categories_check = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getCategoriesbyLevel(1, $this->container->get('request')->getSession()->get('selected_company'));
        unset($categories_check[0]);

        $editForm = $this->createEditForm($userEntity, $userRoles);

        return  $this->display_page('AdminUserManagementBundle:UmUsers:edit.html.twig', array(
            'entity'      => $userEntity,
            'show_active' => $show_active,
            'categories'  => $categories,
            'categories_check' => $categories_check,
            'userRoles'   => $userRoles,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a UmUsers entity.
    *
    * @param UmUsers $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(UmUsers $entity, $userRoles)
    {
        $em = $this->getDoctrine()->getManager();
        $roles = $em->getRepository('AdminUserManagementBundle:UmRoles')->findByStatus(1);
        $companies = $em->getRepository('AdminUserManagementBundle:NomCompanies')->findBy(array('status' => 1),array(), 3);

        $rolesCombo = array();
        $compSelect = array();
        foreach ($roles as $key => $role) {
            $rolesCombo[$role->getId()] = $role->getName();
        }
        foreach ($companies as $key => $company) {
            $compSelect[$company->getId()] = $company->getName();
        }
        $form = $this->createForm(new UmUsersType(), $entity, array(
            'action' => $this->generateUrl('admin_users_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('update', 'button', array(
                'label' => $this->translateWord('update_btn'),
                'attr' => array('class' => 'btn btn-primary',
                                'style' => 'float:left; margin:0 auto',
                                'onclick' => 'javascript:submit_frm(this)')
            ));

        $idx = 0;
        foreach ($userRoles as $roleId => $roleComp) {
            
            $form->add('role'.$idx, 'choice', array(
                'mapped' => false,
                'choices' => $rolesCombo,
                'data'     => $roleId,
                'multiple' => false,
                'expanded' => false))
            ->add('idCompany'.$idx, 'choice', array(
                'mapped' => false,
                'choices' => $compSelect,
                'data'  => $roleComp,
                'multiple' => true,
                'expanded' => true))
            ;
            $idx++;
        }

        return $form;
    }
    /**
     * Edits an existing UmUsers entity.
     *
     */
    public function updateAction(Request $request, $id)
    {

        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2) return $this->redirect_page();

        $em = $this->getDoctrine()->getManager();
        $userEntity = $em->getRepository('AdminUserManagementBundle:UmUsers')->find($id);

        if (!$userEntity) {
            //throw $this->createNotFoundException('Unable to find UmUsers entity.');
            return $this->js_redirect($this->generateUrl('admin_users'));
        }

        $show_active = true;
        if ($id == $this->get('security.context')->getToken()->getUser()->getId()){
            $show_active = false; /*current user can't deactive own account */
        }
        $entity_old = $em->getRepository('AdminUserManagementBundle:UmUsers')->getAsArray($id);
        $userRoles = $em->getRepository('AdminUserManagementBundle:UmUserRoles')->findUserRoles($id);

        $categories = $em->getRepository('AdminUserManagementBundle:UmUsers')->getUserCategories($id);
        $categories_check = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getCategoriesbyLevel(1, $this->container->get('request')->getSession()->get('selected_company'));
        unset($categories_check[0]);

        $editForm = $this->createEditForm($userEntity, $userRoles);
        $editForm->handleRequest($request);

        $conn = $em->getConnection();
        $conn->beginTransaction();

        try{
            if($this->getRequest()->get($editForm->getName())) {

                $reqParams =  $request->request->all();
                $formValues = array();
                if (isset($reqParams['form_umusers'])) {
                    $formValues = $reqParams['form_umusers'];
                } else {
                    throw $this->createNotFoundException('No request.');
                }

                if ($editForm->isValid()) {
                    if ($id == $this->get('security.context')->getToken()->getUser()->getId()){
                        $userEntity->setActive(1);
                    }

                    $em->persist($userEntity);

                    if (isset($reqParams['categ_vals'])) {
                        $categories = $reqParams['categ_vals'];
                        $em->getRepository('AdminUserManagementBundle:UmUsers')->updateUserCategories($reqParams['categ_vals'], $id);
                    } else {
                        $em->getRepository('AdminUserManagementBundle:UmUsers')->updateUserCategories(array(), $id);
                    }

                    if (isset($formValues['role0'])) {

                        $idx = 0;
                        $roles = array();
                        while (isset($formValues['role'.$idx])) {
                            $roles[$idx] = $formValues['role'.$idx];
                            $idx++;
                        }

                        if (count($roles) !== count(array_unique($roles))) {
                            $this->get('session')->getFlashBag()->add('error', $this->translateWord('error_multiple_role'));
                            return $this->redirect($this->generateUrl('admin_users_edit', array('id' => $id)));
                        }


                        for ($idx = 0; $idx < count($roles); $idx++) {
                            if (!isset($formValues['idCompany'.$idx])) {
                                $this->get('session')->getFlashBag()->add('error', $this->translateWord('error_role_nocompany'));
                                return $this->redirect($this->generateUrl('admin_users_edit', array('id' => $id)));
                            }
                        }

                        /* Salvare in DB */
                        $em->getRepository('AdminUserManagementBundle:UmUsers')->refreshRights($conn, $id, $roles, $formValues, 1);

                    } elseif (isset($reqParams['roles'])) {
                        $roles = $reqParams['roles'];

                        $idx = 0;
                        foreach ($roles as $key => $role) {
                            if (!isset($reqParams['idCompany'][$key])) {
                                $this->get('session')->getFlashBag()->add('error', $this->translateWord('error_role_nocompany'));

                                return $this->redirect($this->generateUrl('admin_users_edit', array('id' => $id)));
                            } 
                        }
                        
                        /* Daca nu avem nicio eroare de validare pentru companii*/
                        if (count($roles) !== count(array_unique($roles))) {
                            $this->get('session')->getFlashBag()->add('error', $this->translateWord('error_multiple_role'));

                            return $this->redirect($this->generateUrl('admin_users_edit', array('id' => $id)));
                        }

                        /* Daca nu avem nicio eroare de validare pentru roles*/
                        /* Salvare in DB */
                        $em->getRepository('AdminUserManagementBundle:UmUsers')->refreshRights($conn, $id, $roles, $reqParams['idCompany'], 2);

                    } else {
                        $this->get('session')->getFlashBag()->add('error', $this->translateWord('error_general'));

                        return $this->redirect($this->generateUrl('admin_users_edit', array('id' => $id)));
                    }
                    $em->flush();
                    /* for audit */
                    $entity_new = $em->getRepository('AdminUserManagementBundle:UmUsers')->getAsArray($id);
                    $updates = $this->getDifferences($entity_old, $entity_new);
                    $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find($this->current_node['id_module']);
                    $audit = $this->getAudit('UPDATE', 'User account with id='.$id.' updated. '.$updates, $module, $this->get('security.context')->getToken()->getUser());
                    
                    $em->persist($audit);
                    /***********/
                    $em->flush();
                    $em->getRepository('AdminUserManagementBundle:UmUsers')->refreshRightsCompiled($conn, $userEntity->getId());
                    $conn->commit();

                    $this->get('session')->getFlashBag()->add(
                        'notice',
                        $this->translateWord('um_user_edit_ok')
                    );

                    return $this->js_redirect($this->generateUrl('admin_users_edit', array('id' => $id)));
                }
            }
        } catch (\Exception $e) {
            echo 'Error: ',  $e->getMessage(), "\n";
            $conn->rollback();
            exit;
        }

        return  $this->display_page('AdminUserManagementBundle:UmUsers:edit.html.twig', array(
            'entity'      => $userEntity,
            'show_active' => $show_active,
            'categories'  => $categories,
            'categories_check' => $categories_check,
            'userRoles'   => $userRoles,
            'edit_form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a UmUsers entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $this->getCurrentNode();
        if($this->current_node['access_type'] < 2) return $this->redirect_page();

        if ($id == $this->get('security.context')->getToken()->getUser()->getId()) {
            $this->get('session')->getFlashBag()->add('error', $this->translateWord('crtuser_del_error'));
            return $this->redirect($this->generateUrl('admin_users'));
        }

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AdminUserManagementBundle:UmUsers')->find($id);
        $conn = $em->getConnection();

        if (!$entity) {
            //throw $this->createNotFoundException('Unable to find UmUsers entity.');
            return $this->js_redirect($this->generateUrl('admin_users'));
        }

        $conn = $em->getConnection();
        $conn->beginTransaction();

        try {
            $em->getRepository('AdminUserManagementBundle:UmUsers')->deleteUserRights($id);
            $em->getRepository('AdminUserManagementBundle:UmUsers')->deleteUserCategories($id);
            $em->getRepository('AdminUserManagementBundle:UmUsers')->deleteUserAudit($id);
            $em->getRepository('AdminUserManagementBundle:UmUsers')->sendUserToHistory($id);
            //$entity->setActive(0);
            $em->remove($entity);
            //$em->persist($entity);
            /* for audit */
            $this->getCurrentNode();
            $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find($this->current_node['id_module']);
            $audit = $this->getAudit('DELETE', 'Account with id='.$id.' deleted.', $module);
            $em->persist($audit);
            /***********/
            $em->flush();
            $conn->commit();

        } catch (\Exception $e) {
            $conn->rollback();
            echo 'Error: ',  $e->getMessage(), "\n";
            exit;
        }

        // $this->get('session')->getFlashBag()->add('notice', $this->translateWord('um_user_del_ok'));

        return new JsonResponse(1);
        exit();

        return $this->redirect($this->generateUrl('admin_users'));
    }

    public function resendLinkAction($id) {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AdminUserManagementBundle:UmUsers')->find($id);
        $conn = $em->getConnection();

        if (!$entity) {
            //throw $this->createNotFoundException('Unable to find UmUsers entity.');
            return $this->js_redirect($this->generateUrl('admin_users'));
        }

        $activation_code = $entity->getActivationCode();
        $emails = array('to'=>$entity->getEmail(), 'cc'=>'', 'bcc'=>'');
        $link = $this->generateUrl('confirm_account', array('code' => $activation_code), true);
        $body = sprintf($this->translateWord('message_new_account'),$link, $link, $emails['to']);
        /* also sends mail - to be modified */
        $email = $this->getEmailQueue($emails, $this->translateWord('subject_new_account'), $body, 0, 5);
        $em->persist($email);

        /**** for audit ******/
        $this->getCurrentNode();
        $module = $em->getRepository('AdminUserManagementBundle:UmModules')->find($this->current_node['id_module']);
        $audit = $this->getAudit('RESEND_LINK', 'Activation code for user id='.$id.' sent to '.$entity->getEmail(), $module);
        $em->persist($audit);

        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', $this->translateWord('msg_resend_activation'));

        return $this->js_redirect($this->generateUrl('admin_users_edit', array('id' => $id)));
    }
}
