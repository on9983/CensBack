<?php

namespace App\Service;

use phpDocumentor\Reflection\Types\Boolean;

class LdapConnector
{
    public function __construct(
    ) {
    }

    public function connectionChecker($user_email,$user_pw):bool
    {
        //$user_email= "cn=SITEWEB_DE_VOITURES,dc=domaineassostest,dc=fr";
        //$user_email = "SITEWEB_DE_VOITURES@domaineassostest.fr"
        //$user_pw = "DEF56DEF56&";

        $ldap_con = ldap_connect("WIN-8UIMNU27MV9.nicotestadtest.com",389);

        ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_con, LDAP_OPT_REFERRALS, 0);


        try {
            if($user_pw !=="" && ldap_bind($ldap_con,$user_email,$user_pw))
            {
                ldap_unbind($ldap_con);
                return true; 
            }
            else
            {
                return false;
            }

        } 
        catch(\Exception $e){
            return false;
        }

    }

    // public function getTargetDirectory()
    // {
    //     return $this->targetDirectory;
    // }
}