<?php
declare(strict_types=1);

namespace Muffin\Slug\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Closure;
use InvalidArgumentException;
use Muffin\Slug\Slugger\CakeSlugger;
use Muffin\Slug\SluggerInterface;

/**
 * Slug behavior.
 */
class SlugBehavior extends Behavior
{
    /**
     * Configuration.
     *
     * - field: Name of the field (column) to hold the slug. Defaults to `slug`.
     * - displayField: Name of the field(s) to build the slug from. Defaults to
     *     the `\Cake\ORM\Table::displayField()`.
     * - separator: Defaults to `-`.
     * - replacements: Hash of characters (or strings) to custom replace before
     *     generating the slug.
     * - maxLength: Maximum length of a slug. Defaults to the field's limit as
     *     defined in the schema (when possible). Otherwise, no limit.
     * - slugger: Class that implements the `Muffin\Slug\SlugInterface`.
     *     Can either be a class name or instance. Defaults to
     *     `Muffin\Slug\Slugger\CakeSlugger`.
     * - unique: Tells if slugs should be unique. Set this to a Closure if you
     *     want to customize how unique slugs are generated. Defaults to `true`.
     * - scope: Extra conditions or a Closure used when
     *    checking a slug for uniqueness.
     * - implementedEvents: Events this behavior listens to.  Defaults to
     *    `['Model.buildValidator' => 'buildValidator', 'Model.beforeSave' => 'beforeSave']`.
     *    By default the behavior adds validation for the `displayField` fields
     *    to make them required on record creating. If you don't want these auto
     *    added validations you can set `implementedEvents` to just
     *    `['Model.beforeSave' => 'beforeSave']`.
     * - onUpdate: Boolean indicating whether slug should be updated when
     *   updating record, defaults to `false`.
     * - onDirty: Boolean indicating whether slug should be updated when
     *   slug field is dirty, defaults to `false`.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'field' => 'slug',
        'displayField' => null,
        'separator' => '-',
        'replacements' => [
            '#' => 'hash',
            '?' => 'question',
            '+' => 'and',
            '&' => 'and',
            '"' => '',
            "'" => '',
        ],
        'maxLength' => null,
        'slugger' => CakeSlugger::class,
        'unique' => true,
        'scope' => [],
        'implementedEvents' => [
            'Model.buildValidator' => 'buildValidator',
            'Model.beforeSave' => 'beforeSave',
        ],
        'implementedFinders' => [
            'slugged' => 'findSlugged',
        ],
        'implementedMethods' => [
            'slug' => 'slug',
        ],
        'onUpdate' => false,
        'onDirty' => false,
    ];

    /**
     * Slugger instance.
     *
     * @var \Muffin\Slug\SluggerInterface
     */
    protected $_slugger;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array $config The config for this behavior.
     */
    public function __construct(Table $table, array $config = [])
    {
        if (!empty($config['implementedEvents'])) {
            $this->_defaultConfig['implementedEvents'] = $config['implementedEvents'];
            unset($config['implementedEvents']);
        }
        parent::__construct($table, $config);
    }

    /**
     * Initialize behavior
     *
     * @param array $config The configuration settings provided to this behavior.
     * @return void
     */
    public function initialize(array $config): void
    {
        if (!$this->getConfig('displayField')) {
            $this->setConfig('displayField', $this->_table->getDisplayField());
        }

        if ($this->getConfig('maxLength') === null) {
            /** @psalm-suppress PossiblyNullArrayAccess */
            $this->setConfig(
                'maxLength',
                $this->_table->getSchema()->getColumn($this->getConfig('field'))['length']
            );
        }

        if ($this->getConfig('unique') === true) {
            $this->setConfig('unique', Closure::fromCallable([$this, '_uniqueSlug']));
        }
    }

    /**
     * Get slugger instance.
     *
     * @return \Muffin\Slug\SluggerInterface
     */
    public function getSlugger(): SluggerInterface
    {
        if ($this->_slugger instanceof SluggerInterface) {
            return $this->_slugger;
        }

        return $this->_slugger = $this->_createSlugger($this->getConfig('slugger'));
    }

    /**
     * Set slugger instance.
     *
     * @param \Muffin\Slug\SluggerInterface|string|array $slugger Sets slugger instance.
     *   Can be SluggerInterface instance or class name or config array.
     * @return void
     * @psalm-var \Muffin\Slug\SluggerInterface|class-string|array $slugger
     */
    public function setSlugger($slugger): void
    {
        $this->_slugger = $this->_createSlugger($slugger);
    }

    /**
     * Create slugger instance
     *
     * @param \Muffin\Slug\SluggerInterface|string|array $slugger Sets slugger instance.
     *   Can be SluggerInterface instance or class name or config array.
     * @return \Muffin\Slug\SluggerInterface
     * @psalm-var \Muffin\Slug\SluggerInterface|class-string|array $slugger
     * @psalm-suppress MoreSpecificReturnType
     */
    protected function _createSlugger($slugger): SluggerInterface
    {
        if (is_string($slugger)) {
            /**
             * @psalm-suppress LessSpecificReturnStatement
             * @psalm-suppress InvalidStringClass
             */
            return new $slugger();
        }

        if (is_array($slugger)) {
            $className = $slugger['className'];
            unset($slugger['className']);
            /** @psalm-suppress LessSpecificReturnStatement */
            return new $className($slugger);
        }

        /** @var \Muffin\Slug\SluggerInterface */
        return $slugger;
    }

    /**
     * Returns list of event this behavior is interested in.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return $this->getConfig('implementedEvents');
    }

    /**
     * Callback for Model.buildValidator event.
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event that was fired.
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @param string $name Validator name.
     * @return void
     */
    public function buildValidator(EventInterface $event, Validator $validator, string $name)
    {
        foreach ((array)$this->getConfig('displayField') as $field) {
            if (strpos($field, '.') === false) {
                $validator->requirePresence($field, 'create')
                    ->notEmptyString($field);
            }
        }
    }

    /**
     * Callback for Model.beforeSave event.
     *
     * @param \Cake\Event\EventInterface $event The afterSave event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The entity that was saved.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        $onUpdate = $this->getConfig('onUpdate');
        if (!$entity->isNew() && !$onUpdate) {
            return;
        }

        $onDirty = $this->getConfig('onDirty');
        $field = $this->getConfig('field');
        if (
            !$onDirty
            && $entity->isDirty($field)
            && (!$entity->isNew() || (!empty($entity->{$field})))
        ) {
            return;
        }

        $separator = $this->getConfig('separator');
        if ($entity->isDirty($field) && !empty($entity->{$field})) {
            $slug = $this->slug($entity, $entity->{$field}, $separator);
            $entity->set($field, $slug);

            return;
        }

        $parts = $this->_getPartsFromEntity($entity);
        if (empty($parts)) {
            return;
        }

        $slug = $this->slug($entity, implode($separator, $parts), $separator);
        $entity->set($field, $slug);
    }

    /**
     * Gets the parts from an entity
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @return array
     */
    protected function _getPartsFromEntity(EntityInterface $entity): array
    {
        $parts = [];
        foreach ((array)$this->getConfig('displayField') as $displayField) {
            $value = Hash::get($entity, $displayField);

            if ($value === null && !$entity->isNew()) {
                return [];
            }

            if (!empty($value) || is_numeric($value)) {
                $parts[] = $value;
            }
        }

        return $parts;
    }

    /**
     * Custom finder.
     *
     * @param \Cake\ORM\Query $query Query.
     * @param array $options Options.
     * @return \Cake\ORM\Query Query.
     */
    public function findSlugged(Query $query, array $options): Query
    {
        if (!isset($options['slug'])) {
            throw new InvalidArgumentException('The `slug` key is required by the `slugged` finder.');
        }

        return $query->where([
            $this->_table->aliasField($this->getConfig('field')) => $options['slug'],
        ]);
    }

    /**
     * Generates slug.
     *
     * @param \Cake\Datasource\EntityInterface|string $entity Entity to create slug for
     * @param string $string String to create slug for.
     * @param string|null $separator Separator.
     * @return string Slug.
     */
    public function slug($entity, ?string $string = null, ?string $separator = null): string
    {
        if ($separator === null) {
            $separator = $this->getConfig('separator');
        }

        if (is_string($entity)) {
            if ($string !== null) {
                $separator = $string;
            }
            $string = $entity;
            unset($entity);
        } elseif (($entity instanceof EntityInterface) && $string === null) {
            $string = $this->_getSlugStringFromEntity($entity, $separator);
        }

        /** @psalm-suppress PossiblyNullArgument */
        $slug = $this->_slug($string, $separator);

        $unique = $this->getConfig('unique');
        if (isset($entity) && $unique) {
            $slug = $unique($entity, $slug, $separator);
        }

        return $slug;
    }

    /**
     * Gets the slug string based on an entity
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param string $separator Separator
     * @return string
     */
    protected function _getSlugStringFromEntity(EntityInterface $entity, string $separator): string
    {
        $string = [];
        foreach ((array)$this->getConfig('displayField') as $field) {
            if ($entity->getError($field)) {
                throw new InvalidArgumentException(sprintf(
                    'Error while generating the slug, the field `%s` contains an invalid value.',
                    $field
                ));
            }
            $string[] = Hash::get($entity, $field);
        }

        return implode($separator, $string);
    }

    /**
     * Builds the conditions
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param string $slug Slug
     * @return array
     */
    protected function _conditions(EntityInterface $entity, string $slug): array
    {
        /** @var string $primaryKey */
        $primaryKey = $this->_table->getPrimaryKey();
        $field = $this->_table->aliasField($this->getConfig('field'));

        $conditions = [$field => $slug];

        $scope = $this->getConfig('scope');
        if (is_array($scope)) {
            $conditions += $scope;
        } else {
            $conditions += $scope($entity);
        }

        $id = $entity->get($primaryKey);
        if ($id !== null) {
            /** @psalm-suppress PossiblyInvalidArrayOffset */
            $conditions['NOT'][$this->_table->aliasField($primaryKey)] = $id;
        }

        return $conditions;
    }

    /**
     * Returns a unique slug.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param string $slug Slug.
     * @param string $separator Separator.
     * @return string Unique slug.
     */
    protected function _uniqueSlug(EntityInterface $entity, string $slug, string $separator): string
    {
        $field = $this->_table->aliasField($this->getConfig('field'));
        $conditions = $this->_conditions($entity, $slug);

        $i = 0;
        $suffix = '';
        $length = $this->getConfig('maxLength');

        while ($this->_table->exists($conditions)) {
            $i++;
            $suffix = $separator . $i;
            if ($length && $length < mb_strlen($slug . $suffix)) {
                $slug = mb_substr($slug, 0, $length - mb_strlen($suffix));
            }
            $conditions[$field] = $slug . $suffix;
        }

        return $slug . $suffix;
    }

    /**
     * Proxies the defined slugger's `slug` method.
     *
     * @param string $string String to create a slug from.
     * @param string $separator String to use as separator/separator.
     * @return string Slug.
     */
    protected function _slug(string $string, string $separator): string
    {
        $replacements = $this->getConfig('replacements');
        $slugger = $this->getSlugger();
        /** @psalm-suppress PossiblyNullReference */
        $slug = $slugger->slug(str_replace(array_keys($replacements), $replacements, $string), $separator);
        if (!empty($this->getConfig('maxLength'))) {
            $slug = Text::truncate(
                $slug,
                $this->getConfig('maxLength'),
                ['ellipsis' => '']
            );
        }

        return $slug;
    }
}
