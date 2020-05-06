<?php
namespace ItAces\Types;

use ItAces\UnderAdminControl;

interface UserType extends UnderAdminControl
{
    
    /**
     * Is the current user has access to dashboard or not?
     * 
     * @return boolean
     */
    public function hasDashboardAccess();
    
}
