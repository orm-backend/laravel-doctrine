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
        $role->setCode( config('ormbackend.roles.dashboard', 'dashboard') );
        $role->setName('Admin Panel Users');
        $role->setPermission(0);
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('ormbackend.roles.administrator', 'admin') );
        $role->setName('Administrators');
        $role->setPermission(
            config('ormbackend.perms.entity.create') |
            config('ormbackend.perms.entity.read') |
            config('ormbackend.perms.entity.update') |
            config('ormbackend.perms.entity.delete') |
            config('ormbackend.perms.entity.restore') 
        );
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('ormbackend.roles.default', 'default') );
        $role->setName('Registered Users');
        $role->setPermission(
            config('ormbackend.perms.record.read') |
            config('ormbackend.perms.record.update') |
            config('ormbackend.perms.record.delete') |
            config('ormbackend.perms.record.restore')
        );
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('ormbackend.roles.guest', 'guest') );
        $role->setName('Site Visitors');
        $role->setPermission(
            config('ormbackend.perms.guest.read')
        );
        $role->setSystem(true);
        $em->persist($role);
        
        $em->flush();
    }
}
