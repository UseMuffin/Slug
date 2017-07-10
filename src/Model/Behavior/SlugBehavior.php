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
     * - scope: Extra conditions used when checking a slug for uniqueness.
     * - implementedEvents: Events this behavior listens to.  Defaults to
     *    `['Model.buildValidator' => 'buildValidator', 'Model.beforeSave' => 'beforeSave']`.
     *    By default the behavior adds validation for the `displayField` fields
     *    to make them required on record creating. If you don't want these auto
     *    added validations you can set `implementedEvents` to just
     *    `['Model.beforeSave' => 'beforeSave']`.
     * - onUpdate: Boolean indicating whether slug should be updated when
     *   updating record, defaults to `false`.
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
            "'" => ''
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
        'onUpdate' => false
    ];

    /**
     * Slugger instance
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
    public function initialize(array $config)
    {
        if (!$this->config('displayField')) {
            $this->config('displayField', $this->_table->displayField());
        }

        if ($this->config('maxLength') === null) {
            $this->config('maxLength', $this->_table->schema()->column($this->config('field'))['length']);
        }

        if ($this->config('unique') === true) {
            $this->config('unique', [$this, '_uniqueSlug']);
        }
    }

    /**
     * Get/set slugger instance.
     *
     * @param callable $slugger Sets slugger instance if passed.
     *   If no argument is passed return slugger intance based on behavior config.
     * @return callable|void
     */
    public function slugger($slugger = null)
    {
        if ($slugger !== null) {
            $this->_slugger = $slugger;

            return;
        }

        if ($this->_slugger !== null) {
            return $this->_slugger;
        }

        $slugger = $this->config('slugger');

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
        return $this->config('implementedEvents');
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
        foreach ((array)$this->config('displayField') as $field) {
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
        $config = $this->_config;

        if (!$entity->isNew() && !$config['onUpdate']) {
            return;
        }

        if ($entity->dirty($config['field']) &&
            (!$entity->isNew() || (!empty($entity->{$config['field']})))
        ) {
            return;
        }

        $fields = (array)$config['displayField'];
        $parts = [];
        foreach ($fields as $field) {
            $value = Hash::get($entity, $field);

            if ($value === null && !$entity->isNew()) {
                return;
            }

            if (!empty($value) || is_numeric($value)) {
                $parts[] = $value;
            }
        }

        if (!count($parts)) {
            return;
        }

        $slug = $this->slug($entity, implode($config['separator'], $parts), $config['separator']);
        $entity->set($config['field'], $slug);
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

        return $query->where([$this->_table->aliasField($this->config('field')) => $options['slug']]);
    }

    /**
     * Generates slug.
     *
     * @param \Cake\ORM\Entity|string $entity Entity to create slug for
     * @param string $string String to create slug for.
     * @param string $separator Separator.
     * @return string Slug.
     */
    public function slug($entity, $string = null, $separator = '-')
    {
        if (is_string($entity)) {
            if ($string !== null) {
                $separator = $string;
            }
            $string = $entity;
            unset($entity);
        } elseif (($entity instanceof Entity) && $string === null) {
            $string = [];
            foreach ((array)$this->config('displayField') as $field) {
                if ($entity->errors($field)) {
                    throw new InvalidArgumentException();
                }
                $string[] = $value = Hash::get($entity, $field);
            }
            $string = implode($separator, $string);
        }

        $slug = $this->_slug($string, $separator);

        if (isset($entity) && $unique = $this->config('unique')) {
            $slug = $unique($entity, $slug, $separator);
        }

        return $slug;
    }

    /**
     * Returns a unique slug.
     *
     * @param \Cake\ORM\Entity $entity Entity.
     * @param string $slug Slug.
     * @param string $separator Separator.
     * @return string Unique slug.
     */
    protected function _uniqueSlug(Entity $entity, $slug, $separator = '-')
    {
        $primaryKey = $this->_table->primaryKey();
        $field = $this->_table->aliasField($this->config('field'));

        $conditions = [$field => $slug];
        $conditions += $this->config('scope');
        if ($id = $entity->{$primaryKey}) {
            $conditions['NOT'][$this->_table->aliasField($primaryKey)] = $id;
        }

        $i = 0;
        $suffix = '';
        $length = $this->config('maxLength');

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
        $replacements = $this->config('replacements');
        $callable = $this->slugger();
        if (is_object($callable) && $callable instanceof SluggerInterface) {
            $callable = [$callable, 'slug'];
        }
        $slug = $callable(str_replace(array_keys($replacements), $replacements, $string), $separator);
        if (!empty($this->config('maxLength'))) {
            $slug = Text::truncate(
                $slug,
                $this->config('maxLength'),
                ['ellipsis' => '']
            );
        }

        return $slug;
    }
}
