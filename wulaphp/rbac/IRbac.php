<?php
namespace wulaphp\rbac;

interface IRbac {

    /**
     * Can I do some operation on resource? If I can return true, else return
     * false.
     *
     * @param string $resource 资源.
     * @param Passport $passport 访问者护照.
     * @return mixed 无权操作时返回false,反之返回extra信息
     */
    function icando($resource, $passport);

    /**
     * check if I a the role.
     *
     * @param mixed $role
     * @param Passport
     * @return boolean
     */
    function iam($roles, $passport);
}

?>