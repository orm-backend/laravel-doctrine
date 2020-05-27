# Расширения для интеграции laravel-doctrine

## I. Джейсон-подобное формирование SQL запросов

Без каких-либо дополнительных средств позволяет конвертировать:

1. Из параметров запроса -- автоматически сервером **PHP**
2. В параметры запроса -- http\_build\_query (built-in function) **PHP**
3. Из json -- json_decode (built-in function) **PHP**
4. В json -- json_encode (built-in function) **PHP**
5. Из параметров запроса -- jQuery.deparam **JS** (к сожалению, плагин) 
6. В параметры запроса -- jQuery.param **JS**

При этом HTTP запрос может быть осуществлен любым методом (GET, POST, PUT и т. д.).

Пример создания запроса к БД: 

```PHP
[
    'select' => [
        'user',
        'user.createdBy',
        'user.updatedBy',
        'user.deletedBy'
    ],
    'filter' => [
        [
            "or",
            ["user.updatedBy.name", "like", "administrator"],
            ["user.updatedBy.name", "notLike", "nimda"],
            [
                ["user.id", "isNotNull"],
                ["user.id", "between", 1, 2]
            ]
        ],
        ["user.id", "in", [ 1, 2, 3 ]]
    ],
    'order' => ['user.name', '-user.createdBy.id']
];
```
Его аналог на Doctrine (только фильтрация):  

```PHP
$qb->where(
    $qb->expr()->andX()->addMultiple([
        $qb->expr()->orX()->addMultiple([
            $qb->expr()->like('user_updatedBy.name', ':name1'),
            $qb->expr()->notLike('user_updatedBy.name', ':name2'),
            $qb->expr()->andX()->addMultiple([
                $qb->expr()->notNull('user.id'),
                $qb->expr()->between('user.id', ':id3', ':id4)
            ])
        ]),
        $qb->expr()->in('user.id', ':id5')
    ])
);
```

Результат DQL (только фильтрация):

```SQL
LEFT JOIN user.createdBy user_createdBy
LEFT JOIN user.updatedBy user_updatedBy
LEFT JOIN user.deletedBy user_deletedBy
WHERE (
	user_updatedBy.name LIKE :name1
	OR user_updatedBy.name NOT LIKE :name2
	OR (
		user_updatedBy.id IS NOT NULL AND (user_updatedBy.id BETWEEN :id3 AND :id4)
	)
) AND user.id IN(:id5)
```

Автоматическое формирование LEFT JOIN при необходимости, основываясь на частях select, filter, order. Возможность использования псевдонимов, выражения DQL (только на серверной стороне). Доступные операторы: eq neq gt gte lt lte isNull isNotNull like notLike in notIn between  
Группировка и все с ней связанное не реализовывались умышленно. Расчет, что все статистические запросы будут кодироваться разработчиком самостоятельно. Если же вдруг требуется группировка для отображения данных пользователю, то вероятно, стоит подуать об изменении модели или формирования параметров запроса.

## II. Данная универсальность обмена параметрами и наличие маппинга Doctrine дала возможность вынести из контроллера валидацию связей между сущностями, наличия полей на сущностях, операторов, структуры параметров и очистку введенных пользователем значений со строгой типизацией внутрь этого пакета.

Для облегчения разработки с использованием данного пакета создана и часто используется DevelopmentException с максимально подробым описанием ошибки разработчика.  
Валидация введенных пользователем значений при запросах редактирования производится централизованно внутри модели, независимо от желания разработчика. Это дает возможность гарантировать целостность данных независимо от того, каким способом осуществляется их поставка, будь-то Seeder, Cron, Web, Api или самописный тестовый скрипт. При необходимости дополнительной валидации запроса, например, для проверки размера загружаемого файла, ее следует прописать в контроллере.

## III. Из коробки

После установки и конфигурации пакета имеем:

1. санитарную обработку введенных пользователем данных
2. валидацию введенных пользователем данных
3. фильтрацию прямо по параметрам запроса, к ним даже не нужно обращаться в контроллере
4. сортировку
5. постраничную навигацию
6. готовые сервисы API
7. набор фиксов для интеграции пакета laravel-doctrine
8. интеграцию интерфейса ACL

Пример контроллера API:

```PHP
class CityController extends JsonCRUDController
{
    protected $class = City::class;
}
```

Пример сохранения сущности в веб-контроллере:

```PHP
public function store(Request $request)
{
    $request->validate(User::getRequestValidationRules());
    $this->repository->createOrUpdate(User::class, $request->all());
    $this->repository->em()->flush();

    return redirect()->route('admin.users.index')->with('success', __('User created successfully.'));
}
```

Пример поискового запроса в веб-контроллере:

```PHP
public function index(Request  $request)
{
    return view('admin.user.search', [
        'users' => $this->paginate($this->repository->createQuery(User::class))->appends($request->all())
    ]);
}
```
Этот код уже обладает всем перечисленным "Из коробки".

## IV. Философия (или правильное использование)
Идея Doctrine / Hibernate в том, что он не ходит в БД дважды за одним и тем же объектом. Как миимум втечении одного веб-запроса, но часто и гораздо дольше при использовании длинных сессий или вторичного кеша. Например, пусть в таблице пользователей есть несколько записей, а также у каждой из них селф-референс на создателя и редактора. Мы выбираем простым запросом __без джойнов__ все эти записи. А в гриде они отображаются вместе с именами и адресами создателей и/или редакторов. ORM сама подставляет их в соотвтствии с идентификаторами, не производя никаких дополнительных запросов. Запрос только один.  
При правильной конфигурации и использовании вторичного кеширования можно ожидать, что запросы на выборку данных для их отображения вообще не будут производиться. Если кто-то отредактирует объект, то ORM просто заменит его во вторичном кеше.
Для такого подхода необходимо принять, что **минимальной единицей информации для обмена данными с БД является объект**. Не следует производить выборку отдельных полей объекта. С этим надо согласиться, либо не использовать данные ORM.

## V. Установка

* Подразумевается, что Laravel уже установлен и настроено соединение с базой данных. Добавляем репозиторий в composer.json

```BASH
"repositories": [
    {
       "type": "vcs",
       "url": "git@bitbucket.org:vitaliy_kovalenko/laravel-doctrine.git"
    }
]
```

* Устанавливаем пакеты

```BASH
composer require it-aces/laravel-doctrine
```

* Публикуем сущности User и Role с минимальным набором полей. При необходимости изменяем правила валидации и добавляем новые поля.

```BASH
php artisan vendor:publish --tag="itaces-model"

Copied Directory [/vendor/it-aces/laravel-doctrine/app/Model] To [/app/Model]
Publishing complete.
```

* Публикуем файл конфинурации Doctrine.

```BASH
php artisan vendor:publish --tag="config"

Copied File [/vendor/laravel-doctrine/orm/config/doctrine.php] To [/config/doctrine.php]
Publishing complete.
```

* Необязательно. Для редактирования настроек публикуем файл конфинурации.

```BASH
php artisan vendor:publish --tag="itaces-config"

Copied File [/vendor/it-aces/laravel-doctrine/config/itaces.php] To [/config/itaces.php]
Publishing complete.
```

## VI. Настройка

* Редактируем .env

```BASH
DOCTRINE_PROXY_AUTOGENERATE=1
DOCTRINE_CACHE=file
DOCTRINE_SECOND_CACHE_TTL=3600
DOCTRINE_RESULT_CACHE_TTL=120
```

* Редактируем config/doctrine.php. Устанавливаем managers.default.meta в значение simplified_xml. Так как проект использует Simplified Xml Driver для работы с метаданными сущностей, необходимо, чтобы ключи массива managers.default.paths являлись полными путями к катологам моделей, а значениями пространства имен соответствующих моделей. При разработке модели рекомендуется наследовать сущности от родительский классов (например, в каталоге App\Entities). Это дает возможность управлять маппингом в родительских классах (XML код),  а правила валидации и другие PHP методы прописывать в App\Model. В итоге должно получиться примерно так:

```PHP
'default' => [
    'dev'           => env('APP_DEBUG', false),
    'meta'          => env('DOCTRINE_METADATA', 'simplified_xml'),
    'connection'    => env('DB_CONNECTION', 'mysql'),
    'namespaces'    => [],
    'paths'         => [
        base_path('app/Model') => 'App\Model',
        //base_path('app/Entities') => 'App\Entities',
    ],
    'repository'    => Doctrine\ORM\EntityRepository::class,
    'proxies'       => [
        'namespace'     => false,
        'path'          => storage_path('proxies'),
        'auto_generate' => env('DOCTRINE_PROXY_AUTOGENERATE', false)
    ]
```

* Редактируем config/database.php секцию mysql. Доюавляем ключ connections.mysql.serverVersion и устанавливаем его в значение, соответствующее версии используемого сервера базы данных. Это позволит избежать установок множества соединений для автоопределение версии, когда при использовании кеша фактически запросы к базе не выполняются. Проверяем, что ключ connections.mysql.unix_socket отсутствует или закомментирован. Значению connections.mysql.options присваиваем пустой массив. В итоге:

```PHP
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    //'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'innodb',
    'options' => [
    ],
    'serverVersion' => '5.7.28' // IMPORTANT! prevents queries for auto-detection
],
```

* Редактируем config/auth.php. Устанавливаем значение doctrine для ключа providers.users.driver и проверяем, что в providers.users.model установлен App\Model\User::class. В итоге:

```PHP
'providers' => [
    'users' => [
        'driver' => 'doctrine',
        'model' => App\Model\User::class,
    ],
],
```

## VII. Запуск

* Проверяем маппинг. Должны получить _[Mapping]  OK - The mapping files are correct._ И ошибку _[Database] FAIL - The database schema is not in sync with the current mapping file._

```BASH
php artisan doctrine:schema:validate

Validating for default entity manager...
[Mapping]  OK - The mapping files are correct.
[Database] FAIL - The database schema is not in sync with the current mapping file.
```

* Синхронизируем модель с БД.

```BASH
php artisan doctrine:schema:update
 
Checking if database connected to default entity manager needs updating...
Updating database schema...
Database schema updated successfully! "11" query was executed
```

* Создаем группы и администратора с логином _admin@it-aces.com_ и паролем _doctrine_

```BASH
php artisan db:seed --class="ItAces\Database\Seeds\RoleTableSeeder"
Database seeding completed successfully.

php artisan db:seed --class="ItAces\Database\Seeds\UserTableSeeder"
Database seeding completed successfully.
```

* Запускаем сервер и проверяем доступность сервисов по адресу http://127.0.0.1:8000/api/entities/app-model-user/

```JSON
{"data":[{"createdAt":"1590328480","updatedAt":null,"deletedAt":null,"id":1,"email":"admin@it-aces.com","emailVerifiedAt":"1590328480","createdBy":null,"updatedBy":null,"deletedBy":null,"roles":[{"createdAt":"1590328473","updatedAt":null,"deletedAt":null,"id":2,"code":"admin","name":"Administrators","permission":992,"system":true}]}],"links":{"path":"http:\/\/127.0.0.1:8000\/api\/entities\/app-model-user","first_page_url":"http:\/\/127.0.0.1:8000\/api\/entities\/app-model-user?page=1","prev_page_url":null,"next_page_url":null},"meta":{"current_page":1,"per_page":20,"from":1,"to":1}}
```

## VIII. Далее
Этот пакет использует реализацию по-умолчанию интерфейса ACL, при которой пользователю с ID равным 1 можно абсолютно все, а всем другим, включая не авторизованных, разрешено только чтение. Вы можете создать свою реализацию этого интерфейса _\ItAces\ACL\AccessControl_ и подключить ее в конфигурации itaces.acl. Установка пакета **it-aces/laravel-doctrine-acl** даст возможность сохранять права доступа групп и переопределять их на сущностях в базе данных.
