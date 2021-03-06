<?php
/***************************************************************************
 * Copyright (C) 1999-2012 Gadz.org                                        *
 * http://opensource.gadz.org/                                             *
 *                                                                         *
 * This program is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation; either version 2 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program; if not, write to the Free Software             *
 * Foundation, Inc.,                                                       *
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                   *
 ***************************************************************************/
 
namespace Gorg\Bundle\LdapOrmBundle\Repository;

use Gorg\Bundle\LdapOrmBundle\Ldap\LdapEntityManager;
use Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection;
use Gorg\Bundle\LdapOrmBundle\Ldap\Filter\LdapFilter;

/**
 * Repository for fetching ldap entity
 */
class Repository
{
    protected $em;
    private $class;
    private $entityName;

    /**
     * Build the ldap repository
     * 
     * @param LdapEntityManager $em
     * @param ReflectionClass   $reflectorClass
     */
    public function __construct(LdapEntityManager $em, ClassMetaDataCollection $class)
    {
        $this->em = $em;
        $this->class = $class;
        $this->entityName = $class->name;
    }

    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return array|object The found entity/entities.
     * @throws BadMethodCallException  If the method called is an invalid find* method
     *                                 or no find* method at all and therefore an invalid
     *                                 method call.
     */
    public function __call($method, $arguments)
    {
        switch (true) {
            case (0 === strpos($method, 'findBy')):
                $by = lcfirst(substr($method, 6));
                $method = 'findBy';
		if($this->class->getMeta($by) == null) {
                    if($this->class->isArrayOfLink($by . 's')  == null) {
                        throw new \BadMethodCallException("No sutch ldap attribute $by in $this->entityName");
                    } else {
                        $by = $by . 's';
                        $method = 'findInArray';
                    }
                }
                break;

            case (0 === strpos($method, 'findOneBy')):
                $by = lcfirst(substr($method, 9));
                if($this->class->getMeta($by) == null) {
                    throw new \BadMethodCallException("No sutch ldap attribute $by in $this->entityName");
                }
                $method = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException(
                    "Undefined method '$method'. The method name must start with ".
                    "either findBy or findOneBy!"
                );
        }

        return $this->$method($by,$arguments[0]);
    }

    /**
     * Return list of object 
     * 
     */
    public function findAll()
    {  
        $filter = new LdapFilter(array(
            'objectClass' => $this->class->getObjectClass(),
        ));
        return $this->em->retrieve($filter, $this->entityName);
    }


    /**
     * Return list of object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findBy($varname, $value)
    {
        $attribute = $this->class->getMeta($varname);
        $filter = new LdapFilter(array(
                $attribute => $value,
                'objectClass' => $this->class->getObjectClass(),
        ));
        return $this->em->retrieve($filter, $this->entityName);
    }


    /**
     * Return list of objects with corresponding criteria with or operators
     *
     * @param unknown type $array
     */
    public function findByOr(Array $array)
    {
        $ldapAttributes = array();
        foreach($array as $varname => $value) {
            $ldapAttributes[$this->class->getMeta($varname)] = $value;
        }
        $filter = new LdapFilter($ldapAttributes, "OR");
        return $this->em->retrieve($filter, $this->entityName);
    }

    /**
     * Return an object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findOneBy($varname, $value)
    {
        $attribute = $this->class->getMeta($varname);
        $filter = new LdapFilter(array(
                $attribute => $value,
                'objectClass' => $this->class->getObjectClass(),
        ));

        $arrayOfEntity = $this->em->retrieve($filter, $this->entityName, 1);
        if(isset($arrayOfEntity[0]))
        {
            return $arrayOfEntity[0];
        }
        return null;
    }

    /**
     * Return an object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findInArray($varname, $value) {
        $attribute = $this->class->getMeta($varname);
        $dnToFind = $this->em->buildEntityDn($value);

        $filter = new LdapFilter(array(
                $attribute => $dnToFind,
        ));
        return $this->em->retrieve($filter, $this->entityName);
    }
}
