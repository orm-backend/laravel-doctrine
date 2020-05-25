<?php
namespace ItAces\View;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use ItAces\Uploader;
use ItAces\DBAL\Types\EnumType;
use ItAces\ORM\Entities\EntityBase;
use ItAces\Types\FileType;
use ItAces\Types\ImageType;
use ItAces\Utility\Helper;

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
    
    /**
     * Adding the entity to the container
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
     * Adding the entity container to the container
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
     * Building the class field meta information.
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
     * Converting array data with keys of one format to another format, setting default values ​​and storing files on disk.
     * The input key format looks like this:
     * <code>app-model-class_name</code>
     * The output format:
     * <code>App\Model\ClassName</code>
     *
     * @param array $request
     * @throws \Illuminate\Validation\ValidationException
     * array $storedFiles
     * @return array
     */
    public static function readRequest(array $request, array &$storedFiles = null) : array
    {
        $map = [];
        
        foreach ($request as $classUrlName => $data) {
            if (!is_array($data)) {
                continue;
            }

            $className = Helper::classFromUlr($classUrlName);
            $map[$className] = self::readEntity($className, $data, $storedFiles);
        }
        
        return $map;
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
     * Getting field meta information
     *
     * @return \ItAces\View\MetaField[]
     */
    public function fields()
    {
        return $this->fields;
    }
    
    /**
     * Getting the first entity from the container
     *
     * @return \ItAces\View\WrappedEntity|null
     */
    public function first()
    {
        return isset($this->entities[0]) ? $this->entities[0] : null;
    }
    
    /**
     * Appending a class name to exceprion messages.
     * 
     * @param ValidationException $e
     * @param string $classUrlName
     * @return array
     */
    public static function exceptionToMessages(ValidationException $e, string $classUrlName) : array
    {
        $messages = $e->validator->getMessageBag()->getMessages();
        
        foreach ($messages as $key => $value) {
            $newKey = $classUrlName.'['.$key.']';
            $messages[$newKey] = $value;
            unset($messages[$key]);
        }
        
        return $messages;
    }
    
    /**
     * 
     * @param string $className
     * @param array $data
     * @param array $storedFiles
     * @return array
     */
    protected static function readEntity(string $className, array $data, array &$storedFiles = null) : array
    {
        $entityData = [];
        $entity = new $className();

        /**
         *
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = app('em');
        $classMetadata = $em->getClassMetadata($className);
        $classUrlName = Helper::classToUlr($classMetadata->name);
        $entityFiles = request()->file($classUrlName);
        
        foreach ($classMetadata->associationMappings as $association) {
            $targetEntity = $association['targetEntity'];
            $fieldName = $association['fieldName'];
            
            if (in_array($fieldName, self::INTERNAL_FIELDS)) {
                continue;
            }
            
            if (in_array(FileType::class, class_implements($targetEntity)) && !empty($entityFiles[$fieldName])) {
                $fieldFiles = $entityFiles[$fieldName];
                $inputName = $classUrlName . '[' . $fieldName . ']';
                $type = FileType::class;
                
                if (in_array(ImageType::class, class_implements($targetEntity))) {
                    $type = ImageType::class;
                }
                
                if ($association['type'] & ClassMetadataInfo::TO_ONE) {
                    $fileEntity = self::storeFile($fieldFiles, $targetEntity, $inputName, $type);
                    $entityData[$fieldName] = $fileEntity;
                    
                    if ($storedFiles !== null) {
                        $storedFiles[] = $fileEntity;
                    }
                } else if ($association['type'] & ClassMetadataInfo::TO_MANY) {
                    $entityData[$fieldName] = [];
                    
                    foreach ($fieldFiles as $uploadedFile) {
                        $fileEntity = self::storeFile($uploadedFile, $targetEntity, $inputName, $type);
                        $entityData[$fieldName][] = $fileEntity;
                        
                        if ($storedFiles !== null) {
                            $storedFiles[] = $fileEntity;
                        }
                    }
                }
            } else if (isset($data[$fieldName])) {
                $entityData[$fieldName] = $data[$fieldName];
            }
        }

        foreach ($classMetadata->fieldNames as $fieldName) {
            if ($fieldName != $classMetadata->identifier[0] && in_array($fieldName, self::INTERNAL_FIELDS)) {
                continue;
            }
            
            if (isset($data[$fieldName])) {
                $entityData[$fieldName] = $data[$fieldName];
            } else {
                // Setting default value
                $entityData[$fieldName] = $entity->{$fieldName};
            }
        }

        return $entityData;
    }
    
    /**
     * 
     * @param UploadedFile $uploadedFile
     * @param string $targetEntity
     * @param string $inputName
     * @param string $type
     * @throws \Illuminate\Validation\ValidationException
     * @return \ItAces\ORM\Entities\EntityBase
     */
    protected static function storeFile(UploadedFile $uploadedFile, string $targetEntity, string $inputName, string $type) : EntityBase
    {
        if ($uploadedFile->getError() === UPLOAD_ERR_INI_SIZE) { // Fix Laravel Validation
            throw ValidationException::withMessages([
                $inputName => [__('File size too large.')],
            ]);
        }

        if ($type == ImageType::class) {
            $path = Uploader::storeImage($uploadedFile, $inputName);
        } else {
            $path = Uploader::storeDocument($uploadedFile, $inputName);
        }

        if (!$path) {
            throw ValidationException::withMessages([
                $inputName => [__('Failed to store file.')],
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
            
            if (in_array(FileType::class, class_implements($association['targetEntity']))) {
                $isImage = in_array(ImageType::class, class_implements($association['targetEntity']));
                
                if ($association['type'] & ClassMetadataInfo::TO_ONE) {
                    $fields[] = $isImage ? ImageField::getInstance($classMetadata, $association['fieldName'], $entity) :
                        FileType::getInstance($classMetadata, $association['fieldName'], $entity);
                } else if ($association['type'] & ClassMetadataInfo::TO_MANY) {
                    $collectionField = $isImage ? ImageCollectionField::getInstance($classMetadata, $association['fieldName'], $entity) :
                        FileCollectionField::getInstance($classMetadata, $association['fieldName'], $entity);
                    
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

}
