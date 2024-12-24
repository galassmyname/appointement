<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

       

        // USER MODEL
        $userPermission1 = Permission::create(['name' => 'create: user']);
        $userPermission2 = Permission::create(['name' => 'read: user']);
        $userPermission3 = Permission::create(['name' => 'update: user']);
        $userPermission4 = Permission::create(['name' => 'delete: user']);

        // ROLE MODEL
        $rolePermission1 = Permission::create(['name' => 'create: role']);
        $rolePermission2 = Permission::create(['name' => 'read: role']);
        $rolePermission3 = Permission::create(['name' => 'update: role']);
        $rolePermission4 = Permission::create(['name' => 'delete: role']);

        // PERMISSION MODEL
        $permission1 = Permission::create(['name' => 'create: permission']);
        $permission2 = Permission::create(['name' => 'read: permission']);
        $permission3 = Permission::create(['name' => 'update: permission']);
        $permission4 = Permission::create(['name' => 'delete: permission']);

        // ADMINS
        $adminPermission1 = Permission::create(['name' => 'read: admin']);
        $adminPermission2 = Permission::create(['name' => 'read: admin']);
        $adminPermission3 = Permission::create(['name' => 'update: admin']);
        $adminPermission4 = Permission::create(['name' => 'delete: admin']);


        // DISPONIBILITÉS (Disponibilities)
        $disponibilityPermission1 = Permission::firstOrCreate(['name' => 'create: disponibility']);
        $disponibilityPermission2 = Permission::firstOrCreate(['name' => 'read: disponibility']);
        $disponibilityPermission3 = Permission::firstOrCreate(['name' => 'update: disponibility']);
        $disponibilityPermission4 = Permission::firstOrCreate(['name' => 'delete: disponibility']);
        
        //Rendez-vous 
        $appointmentPermission1 = Permission::firstOrCreate(['name' => 'list: appointment']);
        $appointmentPermission2 = Permission::firstOrCreate(['name' => 'validate: appointment']);
        $appointmentPermission3 = Permission::firstOrCreate(['name' => 'cancel: appointment']);
        $appointmentPermission4 = Permission::firstOrCreate(['name' => 'delete: appointment']);


      

        
        $adminRole = Role::create(['name' => 'admin'])->syncPermissions([
            $userPermission1,
            $userPermission2,
            $userPermission3,
            $userPermission4,
            $rolePermission1,
            $rolePermission2,
            $rolePermission3,
            $rolePermission4,
            $permission1,
            $permission2,
            $permission3,
            $permission4,
            $adminPermission1,
            $adminPermission2,
            $userPermission1,
            $appointmentPermission1,
            $appointmentPermission2,
            $appointmentPermission3,
            $appointmentPermission4,
        ]);
        
        // Création du rôle prestataire avec ses permissions
        $prestataireRole = Role::create(['name' => 'prestataire'])->syncPermissions([
            $userPermission2,
            $userPermission2,
            $permission2,  
            $adminPermission1,
            $disponibilityPermission1,
            $disponibilityPermission2,
            $disponibilityPermission3,
            $disponibilityPermission4,
            $appointmentPermission1,
            $appointmentPermission2,
            $appointmentPermission3,
            $appointmentPermission4,
        ]);
        
        // Création du rôle utilisateur avec ses permissions
        $utilisateurRole = Role::create(['name' => 'utilisateur'])->syncPermissions([
            $userPermission2,
            $userPermission3,
            $adminPermission1,
            $appointmentPermission1,
            $appointmentPermission2,
            $appointmentPermission3,
            $appointmentPermission4,
        ]);


        
        
        // Création d'un utilisateur  Admin
        User::create([
            'name' => 'admin',
            'is_admin' => 1,
            'email' => 'admin@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('passer123'),
            'remember_token' => Str::random(10),
            ])->assignRole($adminRole);
            
            // Création d'un utilisateur prestataire
        User::create([
            'name' => 'prestataire',
            'is_admin' => 0,
            'email' => 'prestataire@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('passer123'),
            'remember_token' => Str::random(10),
            ])->assignRole($prestataireRole);
            
        // Création d'un utilisateur utilisateur
        User::create([
            'name' => 'utilisateur',
            'is_admin' => 0,
            'email' => 'utilisateur@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('passer123'),
            'remember_token' => Str::random(10),
        ])->assignRole($utilisateurRole);

        
        
    }
}


// for($i = 1; $i < 50; $i++){
//     User::create([
//         'name' => 'test',
//         'is_admin' => 0,
//         'email' => 'test'.'@gmail.com',
//         'email_verified_at' => now(),
//         'password' => Hash::make('passer123'),
//         'remember_token' => Str::random(10),
//     ])->assignRole($userRole);
    
// }



// // Création du rôle superAdmin avec toutes les permissions d'admin plus celles de superAdmin
// $superAdminRole = Role::create(['name' => 'superAdmin'])->syncPermissions([
//     $userPermission1,
//     $userPermission2,
//     $userPermission3,
//     $userPermission4,
//     $rolePermission1,
//     $rolePermission2,
//     $rolePermission3,
//     $rolePermission4,
//     $permission1,
//     $permission2,
//     $permission3,
//     $permission4,
//     $adminPermission1,
//     $adminPermission2,
//     $adminPermission3, 
//     $adminPermission4, 
// ]);


        //Création d'un utilisateur superAdmin
        // User::create([
        //     'name' => 'superAdmin',
        //     'is_admin' => 1,
        //     'email' => 'superadmin@gmail.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('passer123'),
        //     'remember_token' => Str::random(10),
        // ])->assignRole($superAdminRole);
