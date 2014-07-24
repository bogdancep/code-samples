<?php

namespace Admin\UserManagementBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * Admin\UserManagementBundle\Entity\UmUsers
 * 
 * @ORM\Entity(repositoryClass="Admin\UserManagementBundle\Entity\UmUsersRepository")
 */



class UmUsers implements AdvancedUserInterface
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $name;

    /**
     * @var boolean
     */
    private $active;

    /**
     * @var \DateTime
     */
    private $lastLogin;

    /**
     * @var integer
     */
    private $locked;

    /**
     * @var integer
     */
    private $passwordExpired;

    /**
     * @var integer
     */
    private $passwordExpireDaysNo;

    /**
     * @var \DateTime
     */
    private $registrationDate;

    /**
     * @var \DateTime
     */
    private $activationDate;
        /**
     * @var string
     */
    private $activationCode;


    /**
     * Get idUser
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return UmUsers
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return UmUsers
     */
    public function setPassword($password)
    {
        $this->password = $password;
    
        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UmUsers
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return UmUsers
     */
    public function setActive($active)
    {
        $this->active = $active;
    
        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     * @return UmUsers
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    
        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime 
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set locked
     *
     * @param integer $locked
     * @return UmUsers
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
    
        return $this;
    }

    /**
     * Get locked
     *
     * @return integer 
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set passwordExpired
     *
     * @param integer $passwordExpired
     * @return UmUsers
     */
    public function setPasswordExpired($passwordExpired)
    {
        $this->passwordExpired = $passwordExpired;
    
        return $this;
    }

    /**
     * Get passwordExpired
     *
     * @return integer 
     */
    public function getPasswordExpired()
    {
        return $this->passwordExpired;
    }

    /**
     * Set passwordExpireDaysNo
     *
     * @param integer $passwordExpireDaysNo
     * @return UmUsers
     */
    public function setPasswordExpireDaysNo($passwordExpireDaysNo)
    {
        $this->passwordExpireDaysNo = $passwordExpireDaysNo;
    
        return $this;
    }

    /**
     * Get passwordExpireDaysNo
     *
     * @return integer 
     */
    public function getPasswordExpireDaysNo()
    {
        return $this->passwordExpireDaysNo;
    }

    /**
     * Set registrationDate
     *
     * @param \DateTime $registrationDate
     * @return UmUsers
     */
    public function setRegistrationDate($registrationDate)
    {
        $this->registrationDate = $registrationDate;
    
        return $this;
    }

    /**
     * Get registrationDate
     *
     * @return \DateTime 
     */
    public function getRegistrationDate()
    {
        return $this->registrationDate;
    }

    /**
     * Set activationDate
     *
     * @param \DateTime $activationDate
     * @return UmUsers
     */
    public function setActivationDate($activationDate)
    {
        $this->activationDate = $activationDate;
    
        return $this;
    }

    /**
     * Get activationDate
     *
     * @return \DateTime 
     */
    public function getActivationDate()
    {
        return $this->activationDate;
    }

        /**
     * Set activationCode
     *
     * @param string $activationCode
     * @return UmUsers
     */
    public function setActivationCode($activationCode)
    {
        $this->activationCode = $activationCode;
    
        return $this;
    }

    /**
     * Get activationCode
     *
     * @return string 
     */
    public function getActivationCode()
    {
        return $this->activationCode;
    }

    public function getRoles() {
        $rolesArr = array(1 => 'ROLE_ADMIN', 2 => 'ROLE_USER', 3 => 'ROLE_EDITOR'); // you should refactor $rolesArr
        return array('ROLE_USER');
    }

    public function getSalt()
    {
        return null;
    }

    public function isAccountNonExpired(){
        return true;
    }

    public function isAccountNonLocked(){
        return true;
    }

    public function isCredentialsNonExpired(){
        return true;
    }

    public function isEnabled(){
        return true;
    }

    public function getUsername(){
        return $this->email;
    }

    public function eraseCredentials(){
        return true;
    }

        /**
     * to string
     *
     * @return string 
     */
    public function __toString()
    {
        return $this->name;
    }

}