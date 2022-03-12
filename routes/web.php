<?php

use App\Models\{
    Comment,
    Course,
    Image,
    Lesson,
    User,
    Preference,
    Module,
    Permission,
    Tag,
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
php artisan tinker
\App\Models\User::factory()->count(10)->create();
*/

Route::get('/', function () {
    echo '<h1>Projeto com todos os relacionamentos</h1>';
});

Route::get('/one-to-one', function () {
    dd('121');
    // Um usuário tem uma preferencia
    // Setando preferencias deste usuário

    // $user = User::first(); // Procura pelo user_id menor
    // $user = User::find(x); // Procura user específico pelo ID
    $user = User::with('preference')->find(2); // ->with() trás também os relacionamentos daquela Model, User com a Preference

    $data = [ // Simulando $request
        'background_color' => '#bbb',
    ];

    if ($user->preference) { // Se já houver preferencias
        $user->preference->update($data); // Atualiza preferencias
    } else { // Caso NULL
        // $user->preference()->create($data); // Cria uma nova

        $preference = new Preference($data); // Instancia uma nova preferencia
        $user->preference()->save($preference); // Salva
    }

    $user->refresh();

    // $user->preference->delete(); //Deleta o objeto 'preferencias' do usuário, retornando à NULL

    dd($user->preference); // Retorna atributo do relacionamento
    // dd($user->preference()->first()); //Retorna collection, NÃO RECOMENDADO pois é uma nova consulta no DB
});

Route::get('/one-to-many', function () {
    dd('12N');
    // Um curso tem um ou vários módulos
    // Um módulo pertence à um curso
    // Um módulo tem uma ou várias lições
    // Uma lição pertence à um módulo

    // $course = Course::create(['name' => 'Curso de Laravel']); // Criando um curso
    // $course = Course::with('modules')->first(); // Procura pelo course_id menor
    // $course = Course::find(x); // Procura curso específico pelo ID x
    // $course = Course::with('modules.lessons')->find(2); // ->with() trás também os relacionamentos daquela Model, Course com as Modules e Lessons
    $course = Course::with('modules.lessons')->first(); // ->with() trás também os relacionamentos daquela Model, Course com as Modules e Lessons
    // dd($course);

    // Simulando view
    echo $course->name;
    echo '<br>';
    foreach ($course->modules as $module) {
        echo "Módulo {$module->name} <br>";

        foreach ($module->lessons as $lesson) {
            echo "Aula {$lesson->name} <br><hr>";
        }
    }
    // -Simulando view-

    // Simulando $request
    $data = [
        'name' => 'Módulo x5'
    ];
    // -Simulando $request-

    //$course->modules()->create($data); // Inserindo a partir do relacionamento pois fica mais enxuto

    // Module::find(7)->update($data); // NÃO RECOMENDADO(Altera todos os registros Para update é melhor alterar o módulo pelo próprio registro por ser independente

    // $course->modules()->get; // NÃO RECOMENDADO pois é uma nova consulta no DB
    $modules = $course->modules; // Retornando os módulos do curso

    dd($modules);
});

Route::get('/many-to-many', function () {
    dd('N2N');
    // Um usuário tem uma ou várias permissões
    // Uma permissão pertence à um ou vários usuários

    // dd(Permission::create([ // Criando permissões
    //     'name' => 'menu_03',
    // ]));

    $user = User::with('permissions')->find(1);

    // $permission = Permission::find(1);
    // $user->permissions()->save($permission);
    // $user->permissions()->saveMany([ // Vincula várias permissões ao mesmo tempo
    //     Permission::find(1),
    //     Permission::find(3),
    // ]);

    // $user->permissions()->sync([1, 3]); // Desvilcula tudo que já tinha, e vincula apenas os novos (pelo ID)
    // Caso haja múltiplos registros iguais (2x adicionado permissão 1), deleta todos e ficam apenas os 2x da 1

    // $user->permissions()->attach([1, 3]); // Vincula várias vezes

    $user->permissions()->syncWithoutDetaching([1, 3]); // Vincula apenas uma vez, fazendo assim não duplicar

    $user->permissions()->detach([1, 3]); // Desvincula todos os vículos especificados, mesmo que sejam múltiplos

    $user->refresh();

    dd($user->permissions);
});

Route::get('/many-to-many-pivot', function () {
    dd('N2N-Pivot');
    // Adicionando e/ou alterando valores default em tabelas pivot

    $user = User::with('permissions')->find(1);

    // Adicionando a pivot table

    $user->permissions()->attach([ // Attaching ao usuário
        4 => [ // ID da permissão
            "active" => false, // "Tupla" => novo valor
        ],
    ]);

    echo "<b>{$user->name}</b> <br>";
    foreach ($user->permissions as $permission) {
        echo "{$permission->name} - {$permission->pivot->active} <br>";
    };
});

Route::get('/one-to-one-polymorphic', function () {
    dd('121-Polymorphic');
    // Criando tabela genérica onde qualquer model pode adicionar valores nela
    // Neste caso, tabela imagem onde User, Course adicionam uma imagem

    $user = User::first();

    $data = [
        'path' => 'path/nome-image2.png',
    ];

    // $user->image->delete(); // Deletar polimorfia entre user e image

    if ($user->image) {
        $user->image->update($data);
    } else {
        // $user->image()->save(new Image($date)); // Preferível utilizar create para não criar instâncias
        $user->image()->create($data);
    };

    dd($user->image);
});

Route::get('/one-to-many-polymorphic', function () {
    dd('12N-Polymorphic');
    // Criando tabela genérica onde qualquer model pode adicionar valores nela
    // Neste caso, tabela comentário onde Course e Lesson adicionam uma comentários
    // Todos os comentário de curso ou aula vão para a tabela única de comentários

    // $course = Course::first();

    // $course->comments()->create([
    //     'subject' => "Título do comentário 3",
    //     'content' => "Corpo do comentário 3",
    // ]);

    // dd($course->comments);

    $comment = Comment::find(1);
    dd($comment->commentable);
});

Route::get('/many-to-many-polymorphic', function () {
    dd('N2N-Polymorphic');
    // TAGS N2N Course
    // Relacionamento genérico de N2N

    $user = User::first();
    // $course = Course::first();

    // Filtrando tudo que esta ligado à esta TAG
    // $tag = Tag::find(1);
    // $tag = Tag::where('name', 'tag2')->first(); // Procurando tag pelo nome
    // dd($tag->users);

    // Criando tags para ter algo à vincular
    // Tag::create(['name' => 'tag1', 'color' => 'red']); 
    // Tag::create(['name' => 'tag2', 'color' => 'blue']);
    // Tag::create(['name' => 'tag3', 'color' => 'green']);

    // $user->tags()->attach([2]);
    // $course->tags()->attach([2]);

    dd($user->tags);
    // dd($course->tags);
});
