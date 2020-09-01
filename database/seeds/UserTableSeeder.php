<?php
namespace OrmBackend\Database\Seeds;

use App\Model\Role;
use App\Model\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $em = app('em');
        $role = $em->getRepository(Role::class)->findOneBy(['code' => config('ormbackend.roles.administrator', 'admin')]);
        $user = new User();
        $user->setEmail('admin@vvk.com');
        $user->setPassword(Hash::make('doctrine'));
        $user->setEmailVerifiedAt( now() );
        $user->addRole($role);
        $em->persist($user);
        $em->flush();
    }
}
