<?php
declare(strict_types=1);

namespace Muffin\Slug\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;
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
     * - slugger: Class that implements the `Muffin\Slug\SluggerInterface`.
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
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [
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
        'onUpdate' => false,
        'onDirty' => false,
    ];

    /**
     * Slugger instance.
     *
     * @var \Muffin\Slug\SluggerInterface|null
     */
    protected ?SluggerInterface $slugger = null;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array<string, mixed> $config The config for this behavior.
     */
    public function __construct(Table $table, array $config = [])
    {
        if (!empty($config['implementedEvents'])) {
            $this->defaultConfig['implementedEvents'] = $config['implementedEvents'];
            unset($config['implementedEvents']);
        }
        parent::__construct($table, $config);
    }

    /**
     * Initialize behavior
     *
     * @param array<string, mixed> $config The configuration settings provided to this behavior.
     * @return void
     */
    public function initialize(array $config): void
    {
        if (!$this->getConfig('displayField')) {
            $this->setConfig('displayField', $this->table->getDisplayField());
        }

        if ($this->getConfig('maxLength') === null) {
            /** @psalm-suppress PossiblyNullArrayAccess */
            $this->setConfig(
                'maxLength',
                // @phpstan-ignore offsetAccess.notFound
                $this->table->getSchema()->getColumn($this->getConfig('field'))['length'],
            );
        }

        if ($this->getConfig('unique') === true) {
            $this->setConfig('unique', $this->uniqueSlug(...));
        }
    }

    /**
     * Get slugger instance.
     *
     * @return \Muffin\Slug\SluggerInterface
     */
    public function getSlugger(): SluggerInterface
    {
        return $this->slugger ??= $this->createSlugger($this->getConfig('slugger'));
    }

    /**
     * Set slugger instance.
     *
     * @param \Muffin\Slug\SluggerInterface|array<string, mixed>|class-string<\Muffin\Slug\SluggerInterface> $slugger Sets slugger instance.
     *   Can be SluggerInterface instance or class name or config array.
     * @return void
     */
    public function setSlugger(SluggerInterface|array|string $slugger): void
    {
        $this->slugger = $this->createSlugger($slugger);
    }

    /**
     * Create slugger instance
     *
     * @param \Muffin\Slug\SluggerInterface|array<string, mixed>|class-string<\Muffin\Slug\SluggerInterface> $slugger Sets slugger instance.
     *   Can be SluggerInterface instance or class name or config array.
     * @return \Muffin\Slug\SluggerInterface
     */
    protected function createSlugger(SluggerInterface|array|string $slugger): SluggerInterface
    {
        if (is_string($slugger)) {
            return new $slugger();
        }

        if (is_array($slugger)) {
            /** @var class-string<\Muffin\Slug\SluggerInterface> $className */
            $className = $slugger['className'];
            unset($slugger['className']);

            return new $className($slugger);
        }

        return $slugger;
    }

    /**
     * Returns list of event this behavior is interested in.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return $this->getConfig('implementedEvents');
    }

    /**
     * Callback for Model.buildValidator event.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The buildValidator event that was fired.
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @param string $name Validator name.
     * @return void
     */
    public function buildValidator(EventInterface $event, Validator $validator, string $name): void
    {
        /** @var string $field */
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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The beforeSave event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The entity that was saved.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $isNew = $entity->isNew();
        if (!$isNew && !$this->getConfig('onUpdate')) {
            return;
        }

        $onDirty = $this->getConfig('onDirty');
        $field = $this->getConfig('field');
        $slugFieldDirty = $entity->isDirty($field);

        if ($onDirty && !$slugFieldDirty && !$isNew) {
            $displayFieldDirty = false;
            foreach ((array)$this->getConfig('displayField') as $df) {
                if ($entity->isDirty($df)) {
                    $displayFieldDirty = true;
                    break;
                }
            }

            if (!$displayFieldDirty) {
                return;
            }
        }

        $separator = $this->getConfig('separator');
        if ($slugFieldDirty && !empty($entity->{$field})) {
            $slug = $this->slug($entity, $entity->{$field}, $separator);
            $entity->set($field, $slug);

            return;
        }

        $parts = $this->getPartsFromEntity($entity);
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
     * @return array<int, mixed>
     */
    protected function getPartsFromEntity(EntityInterface $entity): array
    {
        $parts = [];
        foreach ((array)$this->getConfig('displayField') as $displayField) {
            /** @psalm-suppress InvalidArgument */
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
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<array-key, mixed>> $query Query.
     * @param string $slug Slug to search for.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<array-key, mixed>> Query.
     */
    public function findSlugged(SelectQuery $query, string $slug): SelectQuery
    {
        return $query->where([
            $this->table->aliasField($this->getConfig('field')) => $slug,
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
    public function slug(EntityInterface|string $entity, ?string $string = null, ?string $separator = null): string
    {
        $separator ??= $this->getConfig('separator');

        if (is_string($entity)) {
            if ($string !== null) {
                $separator = $string;
            }
            $string = $entity;
        } elseif ($string === null) {
            $string = $this->getSlugStringFromEntity($entity, $separator);
        }

        $slug = $this->slugify($string, $separator);

        $unique = $this->getConfig('unique');
        if (!is_string($entity) && $unique) {
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
    protected function getSlugStringFromEntity(EntityInterface $entity, string $separator): string
    {
        $string = [];
        foreach ((array)$this->getConfig('displayField') as $field) {
            if ($entity->getError($field)) {
                throw new InvalidArgumentException(sprintf(
                    'Error while generating the slug, the field `%s` contains an invalid value.',
                    $field,
                ));
            }
            /** @psalm-suppress InvalidArgument */
            $string[] = Hash::get($entity, $field);
        }

        return implode($separator, $string);
    }

    /**
     * Builds the conditions
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param string $slug Slug
     * @return array<string, mixed>
     */
    protected function conditions(EntityInterface $entity, string $slug): array
    {
        /** @var string $primaryKey */
        $primaryKey = $this->table->getPrimaryKey();
        $field = $this->table->aliasField($this->getConfig('field'));

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
            $conditions['NOT'][$this->table->aliasField($primaryKey)] = $id;
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
    protected function uniqueSlug(EntityInterface $entity, string $slug, string $separator): string
    {
        $field = $this->table->aliasField($this->getConfig('field'));
        $conditions = $this->conditions($entity, $slug);

        $i = 0;
        $suffix = '';
        $length = (int)$this->getConfig('maxLength');

        while ($this->table->exists($conditions)) {
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
    protected function slugify(string $string, string $separator): string
    {
        /** @var array<string, string> $replacements */
        $replacements = $this->getConfig('replacements');
        $slugger = $this->getSlugger();
        $slug = $slugger->slug(str_replace(array_keys($replacements), $replacements, $string), $separator);
        if ($this->getConfig('maxLength')) {
            $slug = Text::truncate(
                $slug,
                $this->getConfig('maxLength'),
                ['ellipsis' => ''],
            );
        }

        return $slug;
    }
}
