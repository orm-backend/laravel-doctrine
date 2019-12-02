# Расширения для интеграции laravel-doctrine

## I. Битрикс-подобное формирование SQL запросов

Простой джейсон-подобный код. Не требует знания Doctrine. Без каких-либо дополнительных средств позволяет конвертировать:

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

1. уже работающую валидацию пользовательских данных, как для поисковых запросов, так и для запросов редактирования
2. уже работающую фильтрацию прямо по параметрам запроса, к ним даже не нужно обращаться в контроллере
3. уже работающую сортировку
4. уже работающую постраничную навигацию
5. готовые сервисы API
6. набор фиксов для интеграции пакета laravel-doctrine
7. продуманную структуру классов, которые легко использовать по отдельности или переопределять

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

## V. Недостатки
На мой взгляд главным недостатком и отличием от Hibernate является проблема сериализации сущности, ее хранение на сессии. Ее решение в Doctrine возложено на самогенерящиеся классы-прокси. Генерация доступна только в режиме разработки. Эту проблему я видел уже много лет назад и решал ее путем небольшого изменения кода библиотеки с реализацией интерфейса сериализации на сущности. Увы, но Doctrine пошел по другому пути.

## VI. Перспективы
Хотелось бы в итоге получить следующий процесс разработки:

1. создаем UML диаграмму и БД по ней
2. конфигурируем проект
3. генерируем маппинг и классы ORM посредством обратной инженерии 
4. редактируем маппинг
5. и на этом этапе в административной части уже имеем меню, пункты которого обеспечивают доступ к редактированию и полнофункциональному поиску данных в сгенеренных сущностях, с возможностью экспорта в xml, excel
6. создаем контроллеры из нескольких строк кода и данный функционал готов для публичной части
7. пишем что-то кастомное


