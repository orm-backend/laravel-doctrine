<?php
namespace ItAces\Database\Seeds;

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
        $role->setCode( config('itaces.groups.administrators'), 'admins' );
        $role->setName('Administrators');
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('itaces.groups.default', 'default') );
        $role->setName('Registered Users');
        $role->setSystem(true);
        $em->persist($role);
        
        $role = new Role();
        $role->setCode( config('itaces.groups.guests'), 'guests' );
        $role->setName('Site Visitors');
        $role->setSystem(true);
        $em->persist($role);
        
        $em->flush();
    }
}
