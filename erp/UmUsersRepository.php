<?php
namespace Admin\UserManagementBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;


class UmUsersRepository extends EntityRepository implements UserProviderInterface
{

    public function loadUserByUsername($login) {
        $user = $this->findOneBy(array("email" => $login));
        if(!$user)
            throw new AuthenticationException('Invalid username or password');

        return $user;
    }

    public function getAsArray($user_id) {
        $conn = $this->getEntityManager()->getConnection();

        $sql1 = "SELECT a.email, a.name, GROUP_CONCAT(r.name SEPARATOR ',') roles
                    FROM um_users a  INNER JOIN um_user_roles ur ON a.id_user = ur.id_user
                                     INNER JOIN um_roles r ON r.id_role = ur.id_role
                    WHERE a.id_user = :uid";

        $sql2 = "SELECT a.email, a.name, GROUP_CONCAT(uc.id_category SEPARATOR ',') user_categories
                    FROM um_users a LEFT JOIN um_users_categories uc ON a.id_user = uc.id_user
                    WHERE a.id_user = :uid";

        $params = array('uid' => $user_id);
        $results1 = $conn->executeQuery($sql1, $params)->fetch();
        $results2 = $conn->executeQuery($sql2, $params)->fetch();

        if (isset($results2['user_categories'])) 
            $results1['user_categories'] = $results2['user_categories'];
        else
            $results1['user_categories'] = '';

        return $results1;
    }

    public function refreshUser(UserInterface $user) {
        return $this->loadUserByUsername($user->getEmail());
    }

    public function supportsClass($class) {
        return $class === 'AdministrationEndUserManagementBundle\UserManagementBundle\Entity\User';
    }

    public function getGridData($req_info) {

        $select_string = '';
        $where_string = '';
        $conn = $this->getEntityManager()->getConnection();

        $active_key = array_search('a.active = ?', $req_info['map_results']['where_values']);

        if ($active_key !== FALSE) {
            $req_info['map_results']['where_values'][$active_key] = 'LENGTH(a.activation_date) > ?';
            $req_info['map_results']['bind_values'][$active_key] = 0;
        }

        $req_info['map_results']['select_values'][]     = "IF (a.active=1, 'YES', 'NO') AS active";

        $map_results = $req_info['map_results']['select_values'];

        $select_string  = count($req_info['map_results']['select_values'])>0? implode(' , ',$req_info['map_results']['select_values']):'';
        $where_string   = count($req_info['map_results']['where_values'])>0? ' WHERE '.implode(' AND ',$req_info['map_results']['where_values']):'';

        $sql = "SELECT SQL_CALC_FOUND_ROWS ".$select_string.", GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') roles
                        FROM um_users a  LEFT JOIN um_user_roles ur ON a.id_user = ur.id_user
                                INNER JOIN um_roles r ON r.id_role = ur.id_role
            ".$where_string."
          GROUP BY a.id_user
          ORDER BY ".$req_info['sort'].' ' .$req_info['dir']."
          LIMIT  ".$req_info['start'].", ".$req_info['limit']
        ;

        $q = $conn->prepare($sql);

        if(strlen(trim($where_string)) > 0){
            foreach ($req_info['map_results']['bind_values'] as $key => $value) {
                $q->bindValue($key+1, $value);
            }
        }

        $q->execute();
        $results = $q->fetchAll();

        $sql = "SELECT FOUND_ROWS() FOUND_ROWS";
        $q = $conn->prepare($sql);
        $q->execute();
        $records_found = $q->fetchAll();

        $results['results']      = $results;
        $results['records_found']  = $records_found[0]['FOUND_ROWS'];

        return $results;
    }

    // public function getUserRoleNames($conn, $user_id) {
    //     $sql = "SELECT b.name FROM um_user_roles a INNER JOIN um_roles b ON a.id_role=b.id_role
    //                 WHERE  id_user = ?";

    //     $q = $conn->prepare($sql);
    //     $q->bindValue(1, $user_id);
    //     $q->execute();

    //     $final = array();
    //     $results = $q->fetchAll();
    //     foreach ($results as $key => $result) {
    //         $final[$key] = $result['name'];
    //     }
    //     return $final;
    // }

    public function refreshRights($conn, $user_id, $roles, $values, $type) {

        $conn->delete('um_user_roles', array('id_user' => $user_id));

        if ($type == 1) {

            foreach ($roles as $idRole => $role) {
                foreach ($values['idCompany'.$idRole] as $key => $compItem) {
                    //$sql = "INSERT INTO um_user_roles(id_role, id_user, id_company) VALUES ('?', '?', '?') ";

                    $conn->insert('um_user_roles', array(   'id_role' => $role,
                                                            'id_user' => $user_id,
                                                            'id_right' => 1,
                                                            'id_company' => $compItem));
                }
            }
        } elseif ($type == 2) {

            foreach ($roles as $idRole => $role) {
                foreach ($values[$idRole] as $key => $compItem) {
                    $conn->insert('um_user_roles', array(   'id_role' => $role,
                                                            'id_user' => $user_id,
                                                            'id_right' => 1,
                                                            'id_company' => $compItem));
                }
            }
        } else {
            die('ERROR! Invalid type selected!');
        }
    }

    public function refreshRightsCompiled($conn, $user_id) {

        try {
            //$conn->delete('um_user_rights', array('id_user' => $user_id));

            $sql = "SELECT a.id_role, a.id_company, b.id_module, max(b.access_type) AS access_type
                    FROM um_user_roles a, um_roles_modules b, um_modules c
                    WHERE a.id_role=b.id_role AND b.id_module=c.id_module AND id_user = ?
                    GROUP BY a.id_company, b.id_module";

            $q = $conn->prepare($sql);
            $q->bindValue(1, $user_id);
            $q->execute();

            $results = $q->fetchAll();

            foreach ($results as $key => $resItem) {
                $sql = "INSERT INTO um_user_rights (id_user, id_company, id_right, id_module, access_type) VALUES (:uid, :cid, :rid, :mid, :atype)
                    ON DUPLICATE KEY UPDATE id_user = :uid, id_company = :cid, id_right = :rid, id_module = :mid, access_type = :atype";
                $params = array('uid' => $user_id, 'cid' => $resItem['id_company'], 'rid' => 0, 'mid' => $resItem['id_module'], 'atype' => $resItem['access_type']);
                $conn->executeQuery($sql, $params);
                // $conn->insert('um_user_rights', array(  
                //     'id_user' => $user_id,
                //     'id_company' => $resItem['id_company'],
                //     'id_right' => 0,
                //     'id_module' => $resItem['id_module'],
                //     'access_type' => $resItem['access_type'],
                // ));
            }

        } catch (\Exception $e){
            echo 'Insert compilation error:<br/><br/>';
            //$conn->rollback();
            return $e->getMessage();
        }
    }

    /* When a role is edited, all users rights associated with it are modified */
    public function refreshRoleRightsCompiled ($conn, $role_id) {

        $sql = "SELECT a.id_user FROM um_user_roles a WHERE  id_role = ?";

            $q = $conn->prepare($sql);
            $q->bindValue(1, $role_id);
            $q->execute();

            $results = $q->fetchAll();

            foreach ($results as $key => $user) {
               $this->refreshRightsCompiled($conn, $user['id_user']);
            }

    }

    public function getUserCategories($user_id) {
        
        $sql = "SELECT id_category FROM um_users_categories WHERE id_user = ?";
        $q = $this->getEntityManager()->getConnection()->prepare($sql);
        $q->bindValue(1, $user_id);
        $q->execute();
          
        $results = $q->fetchAll();

        $final = array();
        foreach ($results as $key => $result) {
            $final[] = $result['id_category'];
        }

        return $final;
    }

    public function getUserCategoriesWithChildren($user_id) {
        
        $children = array();
        $final = array();

        $em = $this->getEntityManager();
        $sql = "SELECT id_category FROM um_users_categories WHERE id_user = :uid";
        $params = array('uid'=>$user_id);
        $results = $em->getConnection()->executeQuery($sql, $params)->fetchAll();

        foreach ($results as $key => $result) {
            $final[] = $result['id_category'];
            $children = $em->getRepository('AdminMasterDataBundle:MdArticleCategories')->getChildrenArray(array('parent' => $result['id_category'], 'companyId'=>1));
            if (isset($children['entities']) && count($children['entities'])) {
                foreach ($children['entities'] as $key => $child) {
                    $final[] = $key;
                }
            }
        }

        return $final;
    }

    public function updateUserCategories($categories, $user_id) {
        
        $conn = $this->getEntityManager()->getConnection();
        $categories_old = $this->getUserCategories($user_id);

        foreach ($categories_old as $id_categ => $categ_old) {
            if (!in_array($categ_old, $categories)) {
                $sql = "DELETE FROM um_users_categories WHERE id_user = ? AND id_category = ?";
                $q = $conn->prepare($sql);
                $q->bindValue(1, $user_id);
                $q->bindValue(2, $categ_old);
                $q->execute();
            }
        }

        foreach ($categories as $key => $category) {
            if ($category > 0) {

                $sql = "INSERT INTO um_users_categories (id_user, id_category) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE id_user = ?, id_category = ? ";
                $q = $conn->prepare($sql);
                $q->bindValue(1, $user_id);
                $q->bindValue(2, $category);
                $q->bindValue(3, $user_id);
                $q->bindValue(4, $category);

                $q->execute();
            }
        }
    }

    public function getUsersByRight($id_company, $id_module) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT u.email, u.id_user
                FROM um_users u
                INNER JOIN um_user_rights r ON u.id_user = r.id_user
                WHERE r.id_company = :id_company AND r.id_module = :id_module AND r.access_type = 2 AND u.active = 1
                ";
        $params = array('id_company' => $id_company, 'id_module' => $id_module);
                
        $r = $conn->executeQuery($sql, $params)->fetchAll();

        return $r;
    }

    public function deleteUserRights($user_id) {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "DELETE FROM um_user_rights WHERE id_user = :uid ";
        $params = array('uid' => $user_id);
                
        $r = $conn->executeQuery($sql, $params);
    }

    public function deleteUserCategories($user_id) {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "DELETE FROM um_users_categories WHERE id_user = :uid ";
        $params = array('uid' => $user_id);
                
        $r = $conn->executeQuery($sql, $params);
    }

    public function deleteUserAudit($user_id) {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "DELETE FROM um_audit WHERE id_user = :uid ";
        $params = array('uid' => $user_id);
                
        $r = $conn->executeQuery($sql, $params);
    }

    public function sendUserToHistory($user_id) {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "INSERT INTO um_users_hist (SELECT * FROM um_users WHERE id_user = :uid )";
        $params = array('uid' => $user_id);
                
        $r = $conn->executeQuery($sql, $params);
    }
}