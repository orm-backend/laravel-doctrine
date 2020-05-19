<?php
namespace ItAces\View;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ItAces\DBAL\Types\EnumType;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Utility\Helper;
use ItAces\Types\FileType;
use ItAces\Types\ImageType;
use ItAces\Uploader;
use ItAces\ORM\DevelopmentException;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class FieldContainer
{
    const INTERNAL_FIELDS = ['id', 'createdAt', 'updatedAt', 'deletedAt', 'createdBy', 'updatedBy', 'deletedBy'];
    
    const FORBIDDEN_FIELDS = ['password', 'rememberToken'];
    
    /**
     * 
     * @var \ItAces\View\MetaField[]
     */
    protected $fields = [];
    
    /**
     *
     * @var \ItAces\View\WrappedEntity[]
     */
    protected $entities = [];
    
    /**
     * 
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     * 
     * @var boolean
     */
    protected $fetchAllPosibleCollectionValues;
    
    /**
     * 
     * @var array
     */
    protected $enumTypes = [];
    
    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @param bool $fetchAllPosibleCollectionValues
     */
    public function __construct(EntityManager $em, bool $fetchAllPosibleCollectionValues = null)
    {
        $this->em = $em;
        $this->fetchAllPosibleCollectionValues = $fetchAllPosibleCollectionValues;
        $customTypes = config('doctrine.custom_types');
        
        foreach ($customTypes as $name => $class) {
            if ((new \ReflectionClass($class))->isSubclassOf(EnumType::class)) {
                $this->enumTypes[$name] = $class;
            }
        }
    }
    
    public static function exceptionToMessages(ValidationException $e, string $classUrlName) : array
    {
        $messages = $e->validator->getMessageBag()->getMessages();
        
        foreach ($messages as $key => $value) {
            $newKey = $classUrlName.'.'.$key;
            $messages[$newKey] = $value;
            unset($messages[$key]);
        }
        
        return $messages;
    }
    
    /**
     *
     * @param array $data
     * @throws \Illuminate\Validation\ValidationException
     * @return array
     */
    public static function readRequest(array $data) : array
    {
        $map = [];
        
        foreach ($data as $key => $value) {
            if (strrpos($key, '_file')) {
                continue;
            }
            
            [$className, $fieldName] = self::extractFieldName($key);
            
            if (!$className || !$fieldName) {
                continue;
            }

            if (!array_key_exists($className, $map)) {
                $map[$className] = [];
            }

            $map[$className][$fieldName] = $value;
        }

        /**
         *
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = app('em');
        /**
         * 
         * @var \Illuminate\Http\Request $request
         */
        $request = request();
        $files = $request->allFiles();
        
        foreach ($files as $key => $file) {
            $key = substr($key, 0, strrpos($key, '_file'));
            
            if (!$key) {
                // Input name does not ends with _file, ignoring
                continue;
            }
            /**
             *
             * @var \Illuminate\Http\UploadedFile $uploadedFile
             */
            $uploadedFile = $file;
            [$className, $fieldName] = self::extractFieldName($key);
            
            if (!$className || !$fieldName) {
                throw new DevelopmentException("Incorrect input name `{$key}`");
            }
            
            $classMetadata = $em->getClassMetadata($className);
            
            if ($classMetadata->hasAssociation($fieldName)) {
                $association = $classMetadata->getAssociationMapping($fieldName);
                
                if (in_array(FileType::class, class_implements($association['targetEntity']))) {
                    $isImage = in_array(ImageType::class, class_implements($association['targetEntity']));
                    $targetEntity = $association['targetEntity'];
                    Validator::make([$fieldName => $uploadedFile], $targetEntity::getRequestValidationRules())->validate();
                    
                    if ($association['type'] & ClassMetadataInfo::TO_ONE) {
                        $map[$className][$fieldName] = self::storeFile($uploadedFile, $targetEntity, $key, $isImage);
                    } else if ($association['type'] & ClassMetadataInfo::TO_MANY) {
                        if (!is_array($map[$className][$fieldName])) {
                            $map[$className][$fieldName] = [];
                        }
                        
                        if (is_array($uploadedFile)) {
                            foreach ($uploadedFile as $file) {
                                $map[$className][$fieldName][] = self::storeFile($file, $targetEntity, $key, $isImage);
                            }
                        } else {
                            $map[$className][$fieldName][] = self::storeFile($uploadedFile, $targetEntity, $key, $isImage);
                        }
                    }
                }
            }
        }

        return $map;
    }
    
    /**
     * 
     * @param UploadedFile $uploadedFile
     * @param string $targetEntity
     * @param string $key
     * @param bool $isImage
     * @throws \Illuminate\Validation\ValidationException
     * @return \ItAces\ORM\Entities\EntityBase
     */
    protected static function storeFile(UploadedFile $uploadedFile, string $targetEntity, string $key, bool $isImage) : EntityBase
    {
        if ($isImage) {
            $path = Uploader::storeImage($uploadedFile, $key);
        } else {
            $path = Uploader::storeDocument($uploadedFile, $key);
        }

        if (!$path) {
            throw ValidationException::withMessages([
                $key => [__('Failed to store file.')],
            ]);
        }

        /**
         *
         * @var \ItAces\View\Types\FileType $targetEntity
         */
        $fileEntity = new $targetEntity;
        $fileEntity->setName($uploadedFile->getClientOriginalName());
        $fileEntity->setPath($path);
        
        return $fileEntity;
    }
    
    static protected function extractFieldName(string $key) : array
    {
        $className = null;
        $fieldName = null;
        $lastUnderscore = strripos($key, '_');
        
        if ($lastUnderscore) {
            $classUrlName = substr($key, 0, strripos($key, '_'));
            $fieldName = substr($key, strripos($key, '_') + 1);
            $className = Helper::classFromUlr($classUrlName);
        }
        
        return [$className, $fieldName];
    }
    
    /**
     * 
     * @param \ItAces\ORM\Entities\EntityBase $entity
     */
    public function addEntity(EntityBase $entity)
    {
        $className = get_class($entity);
        $classMetadata = $this->em->getClassMetadata($className);
        $this->entities[] = $this->wrapEntity($classMetadata, $entity);
    }
    
    /**
     * 
     * @param \ItAces\ORM\Entities\EntityBase[] $data
     */
    public function addCollection(array $data)
    {
        foreach ($data as $entity) {
            $this->addEntity($entity);
        }
    }
    
    /**
     *
     * @param ClassMetadata $classMetadata
     * @param EntityBase $instance
     * @return \ItAces\View\WrappedEntity
     */
    protected function wrapEntity(ClassMetadata $classMetadata, EntityBase $entity) : WrappedEntity
    {
        $fields = [BaseField::getInstance($classMetadata, 'id', $entity)];
        $fields = array_merge($fields, $this->buildMetadataOfSimpleFields($classMetadata, $entity));
        $fields = array_merge($fields, $this->buildMetadataOfFileFields($classMetadata, $entity));
        $fields = array_merge($fields, $this->buildMetadataOfAssociationFields($classMetadata, $entity));
        $fields = array_merge($fields, $this->buildMetadataOfInternalFields($classMetadata, $entity));
        
        $wrapped = new WrappedEntity($entity);
        
        foreach ($fields as $field) {
            $wrapped->addField($field);
        }
        
        return $wrapped;
    }

    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     */
    public function buildMetaFields(ClassMetadata $classMetadata)
    {
        $this->fields[] = BaseField::getInstance($classMetadata, 'id');
        $this->fields = array_merge($this->fields, $this->buildMetadataOfSimpleFields($classMetadata));
        $this->fields = array_merge($this->fields, $this->buildMetadataOfFileFields($classMetadata));
        $this->fields = array_merge($this->fields, $this->buildMetadataOfAssociationFields($classMetadata));
        $this->fields = array_merge($this->fields, $this->buildMetadataOfInternalFields($classMetadata));
    }

    /**
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param \ItAces\ORM\Entities\EntityBase $instance
     * @return \ItAces\View\MetaField[]
     */
    protected function buildMetadataOfFileFields(ClassMetadata $classMetadata, EntityBase $entity = null)
    {
        $fields = [];
        
        foreach ($classMetadata->associationMappings as $association) {
            if (!in_array(FileType::class, class_implements($association['targetEntity'])) ||
                !$association['isOwningSide']) {
                continue;
            }
            
            if (!Gate::check('read', Helper::classToUlr($association['targetEntity']))) {
                continue;
            }

            if (in_array(ImageType::class, class_implements($association['targetEntity']))) {
                if ($association['type'] & ClassMetadataInfo::TO_ONE) {
                    $fields[] = ImageField::getInstance($classMetadata, $association['fieldName'], $entity);
                } else if ($association['type'] & ClassMetadataInfo::TO_MANY) {
                    $collectionField = ImageCollectionField::getInstance($classMetadata, $association['fieldName'], $entity);
                    
                    if ($this->fetchAllPosibleCollectionValues) {
                        $collectionField->fetchAllValues();
                    }
                    
                    $fields[] = $collectionField;
                }
            } else {
                if ($association['type'] & ClassMetadataInfo::TO_ONE) {
                    $fields[] = FileField::getInstance($classMetadata, $association['fieldName'], $entity);
                } else if ($association['type'] & ClassMetadataInfo::TO_MANY) {
                    $collectionField = FileCollectionField::getInstance($classMetadata, $association['fieldName'], $entity);
                    
                    if ($this->fetchAllPosibleCollectionValues) {
                        $collectionField->fetchAllValues();
                    }
                    
                    $fields[] = $collectionField;
                }
            }
        }
        
        return $fields;
    }
    
    /**
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param \ItAces\ORM\Entities\EntityBase $instance
     * @return \ItAces\View\MetaField[]
     */
    protected function buildMetadataOfSimpleFields(ClassMetadata $classMetadata, EntityBase $entity = null)
    {
        $fields = [];
        
        foreach ($classMetadata->fieldNames as $fieldName) {
            if (array_search($fieldName, self::INTERNAL_FIELDS) !== false) {
                continue;
            }
            
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            
            if (array_key_exists($fieldMapping['type'], $this->enumTypes)) {
                $enumField = EnumField::getInstance($classMetadata, $fieldName, $entity);
                $enumField->initOptions($this->enumTypes[$fieldMapping['type']]);
                $fields[] = $enumField;
                continue;
            }
            
            $fields[] = BaseField::getInstance($classMetadata, $fieldName, $entity);
        }
        
        return $fields;
    }
    
    /**
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param \ItAces\ORM\Entities\EntityBase $instance
     * @return \ItAces\View\MetaField[]
     */
    protected function buildMetadataOfAssociationFields(ClassMetadata $classMetadata, EntityBase $entity = null)
    {
        $fields = [];
        
        foreach ($classMetadata->associationMappings as $association) {
            if (array_search($association['fieldName'], self::INTERNAL_FIELDS) !== false ||
                in_array(FileType::class, class_implements($association['targetEntity']))) {
                continue;
            }
            
            if (!Gate::check('read', Helper::classToUlr($association['targetEntity']))) {
                continue;
            }
            
            if ($association['type'] & ClassMetadataInfo::TO_ONE) {
                if (!$association['isOwningSide']) {
                    continue;
                }
                
                $fields[] = ReferenceField::getInstance($classMetadata, $association['fieldName'], $entity);
            } else if ($association['type'] & ClassMetadataInfo::TO_MANY) {
                if (!$association['isOwningSide']) {
                    continue;
                }
                
                $collectionField = CollectionField::getInstance($classMetadata, $association['fieldName'], $entity);
                
                if ($this->fetchAllPosibleCollectionValues) {
                    $collectionField->fetchAllValues();
                }
                
                $fields[] = $collectionField;
            }
        }
        
        return $fields;
    }
    
    /**
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $classMetadata
     * @param \ItAces\ORM\Entities\EntityBase $instance
     * @return \ItAces\View\MetaField[]
     */
    protected function buildMetadataOfInternalFields(ClassMetadata $classMetadata, EntityBase $entity = null)
    {
        $fields = [];
        
        foreach (self::INTERNAL_FIELDS as $fieldName) {
            if ($fieldName == 'id') {
                continue;
            }
            
            if (array_search($fieldName, $classMetadata->fieldNames) !== false) {
                $fields[] = BaseField::getInstance($classMetadata, $fieldName, $entity);
            } else if ($classMetadata->hasAssociation($fieldName)) {
                $association = $classMetadata->getAssociationMapping($fieldName);
                
                if (Gate::check('read', Helper::classToUlr($association['targetEntity']))) {
                    $fields[] = ReferenceField::getInstance($classMetadata, $fieldName, $entity);
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * 
     * @return \ItAces\View\WrappedEntity[]
     */
    public function entities()
    {
        return $this->entities;
    }
    
    /**
     * 
     * @return \ItAces\View\MetaField[]
     */
    public function fields()
    {
        return $this->fields;
    }
    
    /**
     * 
     * @return \ItAces\View\WrappedEntity|null
     */
    public function first()
    {
        return isset($this->entities[0]) ? $this->entities[0] : null;
    }

}
