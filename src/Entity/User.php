<?php

namespace App\Entity;

use App\Annotation\Document;

/**
 * @Document\Table("user")
 */
class User extends AbstractEntity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var string
     */
    protected $tenant;

    /**
     * @var string|null
     */
    protected $apikey;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string[]
     */
    protected $role = [];

    /**
     * @var array|null
     */
    protected $searchOptions;

    /**
     * @var string|null
     */
    protected $conceptSelection;

    /**
     * @var string|null
     */
    protected $defaultSearchProfileIds;

    /**
     * @var bool
     */
    protected $disableSearchProfileChanging;

    /**
     * @var string|null
     */
    protected $uri;

    /**
     * @var bool
     */
    protected $enableSkosXl;

    /**
     * @param int $id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $email
     *
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $password A plain or hashed password
     * @param bool        $hash     Whether or not the password needs to be hashed
     *
     * @return self
     */
    public function setPassword($password, $hash = false)
    {
        /* if ($hash) { */
        /*     $password = HASH($password); */
        /* } */
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $tenant
     *
     * @return self
     */
    public function setTenant($tenant)
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * @return string
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * @param string $apikey
     *
     * @return self
     */
    public function setApikey($apikey)
    {
        $this->apikey = $apikey;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApikey()
    {
        return $this->apikey;
    }

    /**
     * @param mixed $active
     *
     * @return self
     */
    public function setActive($active)
    {
        if ('Y' === $active) {
            $active = true;
        }
        $this->active = (bool) $active;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $role
     *
     * @return self
     */
    public function setRole($role)
    {
        if (is_string($role)) {
            $role = str_getcsv($role);
        }
        if (!(is_array($role) || is_null($role))) {
            return $this;
        }

        $this->role = $role;

        return $this;
    }

    /**
     * @return array
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $searchOptions
     *
     * @return self
     */
    public function setSearchOptions($searchOptions)
    {
        if (is_string($searchOptions)) {
            $searchOptions = json_decode($searchOptions, true);
        }

        $this->searchOptions = $searchOptions;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getSearchOptions()
    {
        return $this->searchOptions;
    }

    /**
     * @param string|null $conceptSelection
     *
     * @return self
     */
    public function setConceptSelection($conceptSelection)
    {
        $this->conceptSelection = $conceptSelection;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getConceptSelection()
    {
        return $this->conceptSelection;
    }

    /**
     * @param string|null $defaultSearchProfileIds
     *
     * @return self
     */
    public function setDefaultSearchProfileIds($defaultSearchProfileIds)
    {
        $this->defaultSearchProfileIds = $defaultSearchProfileIds;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDefaultSearchProfileIds()
    {
        return $this->defaultSearchProfileIds;
    }

    /**
     * @param mixed $disableSearchProfileChanging
     *
     * @return self
     */
    public function setDisableSearchProfileChanging($disableSearchProfileChanging)
    {
        if ('Y' === $disableSearchProfileChanging) {
            $disableSearchProfileChanging = true;
        }
        $this->disableSearchProfileChanging = (bool) $disableSearchProfileChanging;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisableSearchProfileChaning()
    {
        return $this->disableSearchProfileChanging;
    }

    /**
     * @param string|null $uri
     *
     * @return self
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed|null $enableSkosXl
     *
     * @return self
     */
    public function setEnableSkosXl($enableSkosXl)
    {
        if ('Y' === $enableSkosXl) {
            $enableSkosXl = true;
        }
        $this->enableSkosXl = (bool) $enableSkosXl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnableSkosXl()
    {
        return $this->enableSkosXl;
    }
}
