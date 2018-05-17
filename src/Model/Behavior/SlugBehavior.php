<?php
namespace Muffin\Slug\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use InvalidArgumentException;
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
     * - slugger: Class that implements the `Muffin\Slug\SlugInterface`. Defaults
     *     to `Muffin\Slug\Slugger\CakeSlugger`.
     * - unique: Tells if slugs should be unique. Set this to a callable if you
     *     want to customize how unique slugs are generated. Defaults to `true`.
     * - scope: Extra conditions or a callable `$callable($entity)` used when
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
        'slugger' => 'Muffin\Slug\Slugger\CakeSlugger',
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
     * Slugger instance or callable
     *
     * @var \Muffin\Slug\SluggerInterface|callable
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
    public function initialize(array $config)
    {
        if (!$this->getConfig('displayField')) {
            $this->setConfig('displayField', $this->_table->getDisplayField());
        }

        if ($this->getConfig('maxLength') === null) {
            $this->setConfig(
                'maxLength',
                $this->_table->getSchema()->getColumn($this->getConfig('field'))['length']
            );
        }

        if ($this->getConfig('unique') === true) {
            $this->setConfig('unique', [$this, '_uniqueSlug']);
        }
    }

    /**
     * Get/set slugger instance.
     *
     * @param \Muffin\Slug\SluggerInterface|callable $slugger Sets slugger instance if passed.
     *   If no argument is passed return slugger intance based on behavior config.
     * @return callable|\Muffin\Slug\SluggerInterface|null
     */
    public function slugger($slugger = null)
    {
        if ($slugger !== null) {
            $this->_slugger = $slugger;

            return null;
        }

        if ($this->_slugger !== null) {
            return $this->_slugger;
        }

        $slugger = $this->getConfig('slugger');

        if (is_string($slugger)) {
            $this->_slugger = new $slugger();
        } elseif (is_array($slugger) && isset($slugger['className'])) {
            $this->_slugger = new $slugger['className']();
            unset($slugger['className']);
            $this->_slugger->config = $slugger + $this->_slugger->config;
        } else {
            $this->_slugger = $slugger;
        }

        return $this->_slugger;
    }

    /**
     * Returns list of event this behavior is interested in.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return $this->getConfig('implementedEvents');
    }

    /**
     * Callback for Model.buildValidator event.
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired.
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @param string $name Validator name.
     * @return void
     */
    public function buildValidator(Event $event, Validator $validator, $name)
    {
        foreach ((array)$this->getConfig('displayField') as $field) {
            if (strpos($field, '.') === false) {
                $validator->requirePresence($field, 'create')
                    ->notEmpty($field);
            }
        }
    }

    /**
     * Callback for Model.beforeSave event.
     *
     * @param \Cake\Event\Event $event The afterSave event that was fired.
     * @param \Cake\ORM\Entity $entity The entity that was saved.
     * @param \ArrayObject $options Options.
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $onUpdate = $this->getConfig('onUpdate');
        if (!$entity->isNew() && !$onUpdate) {
            return;
        }

        $onDirty = $this->getConfig('onDirty');
        $field = $this->getConfig('field');
        if (!$onDirty
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
    protected function _getPartsFromEntity($entity)
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
    public function findSlugged(Query $query, array $options)
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
     * @param \Cake\ORM\Entity|string $entity Entity to create slug for
     * @param string $string String to create slug for.
     * @param string|null $separator Separator.
     * @return string Slug.
     */
    public function slug($entity, $string = null, $separator = null)
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
        } elseif (($entity instanceof Entity) && $string === null) {
            $string = $this->_getSlugStringFromEntity($entity, $separator);
        }

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
    protected function _getSlugStringFromEntity($entity, $separator)
    {
        $string = [];
        foreach ((array)$this->getConfig('displayField') as $field) {
            if ($entity->getError($field)) {
                throw new InvalidArgumentException(sprintf('Error while generating the slug, the field `%s` contains an invalid value.', $field));
            }
            $string[] = $value = Hash::get($entity, $field);
        }

        return implode($separator, $string);
    }

    /**
     * Builds the conditions
     *
     * @param \Cake\ORM\Entity $entity Entity.
     * @param string $slug Slug
     * @return array
     */
    protected function _conditions($entity, $slug)
    {
        /** @var string $primaryKey */
        $primaryKey = $this->_table->getPrimaryKey();
        $field = $this->_table->aliasField($this->getConfig('field'));

        $conditions = [$field => $slug];

        if (is_callable($this->getConfig('scope'))) {
            $scope = $this->getConfig('scope');
            $conditions += $scope($entity);
        } else {
            $conditions += $this->getConfig('scope');
        }

        if ($id = $entity->{$primaryKey}) {
            $conditions['NOT'][$this->_table->aliasField($primaryKey)] = $id;
        }

        return $conditions;
    }

    /**
     * Returns a unique slug.
     *
     * @param \Cake\ORM\Entity $entity Entity.
     * @param string $slug Slug.
     * @param string $separator Separator.
     * @return string Unique slug.
     */
    protected function _uniqueSlug(Entity $entity, $slug, $separator)
    {
        /** @var string $primaryKey */
        $primaryKey = $this->_table->getPrimaryKey();
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
     * @param  string $separator String to use as separator/separator.
     * @return string Slug.
     */
    protected function _slug($string, $separator)
    {
        $replacements = $this->getConfig('replacements');
        $callable = $this->slugger();
        if (is_object($callable) && $callable instanceof SluggerInterface) {
            $callable = [$callable, 'slug'];
        }
        $slug = $callable(str_replace(array_keys($replacements), $replacements, $string), $separator);
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
