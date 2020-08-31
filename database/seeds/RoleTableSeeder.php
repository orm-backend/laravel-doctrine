<?php
namespace OrmBackend\Database\Seeds;

use App\Model\Role;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $em = app('em');
        
        $role = new Role();
        $role->setCode( config('itaces.roles.dashboard', 'dashboard') );
        $role->setName('Admin Panel Users');
        $role->setPermission(0);
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('itaces.roles.administrator', 'admin') );
        $role->setName('Administrators');
        $role->setPermission(
            config('itaces.perms.entity.create') |
            config('itaces.perms.entity.read') |
            config('itaces.perms.entity.update') |
            config('itaces.perms.entity.delete') |
            config('itaces.perms.entity.restore') 
        );
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('itaces.roles.default', 'default') );
        $role->setName('Registered Users');
        $role->setPermission(
            config('itaces.perms.record.read') |
            config('itaces.perms.record.update') |
            config('itaces.perms.record.delete') |
            config('itaces.perms.record.restore')
        );
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('itaces.roles.guest', 'guest') );
        $role->setName('Site Visitors');
        $role->setPermission(
            config('itaces.perms.guest.read')
        );
        $role->setSystem(true);
        $em->persist($role);
        
        $em->flush();
    }
}
