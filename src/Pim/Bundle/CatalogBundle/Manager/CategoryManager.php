<?php

namespace Pim\Bundle\CatalogBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Pim\Bundle\CatalogBundle\Model\CategoryInterface;

/**
 * Category manager
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CategoryManager
{
    /** @var ObjectManager */
    protected $om;

    /** @var string */
    protected $categoryClass;

    /**
     * Constructor
     *
     * @param ObjectManager $om
     * @param string        $categoryClass
     */
    public function __construct(ObjectManager $om, $categoryClass)
    {
        $this->om = $om;
        $this->categoryClass = $categoryClass;
    }

    /**
     * Return object manager
     *
     * @return ObjectManager
     *
     * // TODO: DEGAGE
     */
    public function getObjectManager()
    {
        return $this->om;
    }

    /**
     * Return the entity repository reponsible for the category
     *
     * @return CategoryRepository
     *
     * TODO: Inject CategoryRepository
     * TODO: DEGAGE
     */
    public function getEntityRepository()
    {
        return $this->om->getRepository(new $this->categoryClass());
    }

    /**
     * Get a new tree instance
     *
     * @return CategoryInterface
     *
     * // TODO: DEGAGE
     */
    public function getTreeInstance()
    {
        $tree = new $this->categoryClass();
        $tree->setParent(null);

        return $tree;
    }

    /**
     * {@inheritdoc}
     *
     * // TODO: DEGAGE, to put in repo
     */
    public function getTrees()
    {
        return $this->getEntityRepository()->getChildren(null, true, 'created', 'DESC');
    }

    /**
     * Get all direct children for a parent category id.
     * If the $selectNodeId is provided, all the children
     * level needed to provides the selectNode are returned
     *
     * @param int $parentId
     * @param int $selectNodeId
     *
     * @return ArrayCollection
     *
     * // TODO: DEGAGE, to put in repo
     */
    public function getChildren($parentId, $selectNodeId = false)
    {
        $children = array();

        $entityRepository = $this->getEntityRepository();

        if ($selectNodeId === false) {
            $children = $entityRepository->getChildrenByParentId($parentId);
        } else {
            $children = $entityRepository->getChildrenTreeByParentId($parentId, $selectNodeId);
        }

        return $children;
    }

    /**
     * {@inheritdoc}
     */
    public function getTreeChoices()
    {
        $trees = $this->getTrees();
        $choices = array();
        foreach ($trees as $tree) {
            $choices[$tree->getId()] = $tree->getLabel();
        }

        return $choices;
    }

    /**
     * Get a collection of categories based on the array of id provided
     *
     * @param array $categoriesIds
     *
     * @return Collection of categories
     *
     *  // TODO: DEGAGE
     */
    public function getCategoriesByIds($categoriesIds)
    {
        return $this->getEntityRepository()->getCategoriesByIds($categoriesIds);
    }

    /**
     * Provides a tree filled up to the categories provided, with all their ancestors
     * and ancestors sibligns are filled too, in order to be able to display the tree
     * directly without loading other data.
     *
     * @param CategoryInterface $root       Tree root category
     * @param Collection        $categories categories
     *
     * @return array Multi-dimensional array representing the tree
     *
     * // TODO: DEGAGE, try it in SQL
     */
    public function getFilledTree(CategoryInterface $root, Collection $categories)
    {
        $parentsIds = array();

        foreach ($categories as $category) {
            $categoryParentsIds = array();
            $path = $this->getEntityRepository()->getPath($category);

            if ($path[0]->getId() === $root->getId()) {
                foreach ($path as $pathItem) {
                    $categoryParentsIds[] = $pathItem->getId();
                }
            }
            $parentsIds = array_merge($parentsIds, $categoryParentsIds);
        }
        $parentsIds = array_unique($parentsIds);

        return $this->getEntityRepository()->getTreeFromParents($parentsIds);
    }

    /**
     * Get tree by code
     *
     * @param string $code
     *
     * @return CategoryInterface
     *
     * // TODO: DEGAGE to put in repo
     */
    public function getTreeByCode($code)
    {
        return $this
            ->getEntityRepository()
            ->findOneBy(array('code' => $code, 'parent' => null));
    }

    /**
     * Get category by code
     *
     * @param string $code
     *
     * @return CategoryInterface
     *
     * // TODO: DEGAGE to put in repo
     */
    public function getCategoryByCode($code)
    {
        return $this
            ->getEntityRepository()
            ->findOneBy(array('code' => $code));
    }

    /**
     * Move a category to another parent
     * If $prevSiblingId is provided, the category will be positioned after this
     * category, otherwise it will be the first child of the parent category
     *
     * @param int $categoryId
     * @param int $parentId
     * @param int $prevSiblingId
     *
     * TODO: DEGAGE, TOU POUTE in category mover, NO ID
     */
    public function move($categoryId, $parentId, $prevSiblingId)
    {
        $repo     = $this->getEntityRepository();
        $category = $repo->find($categoryId);
        $parent   = $repo->find($parentId);
        $prevSibling = null;

        $category->setParent($parent);

        if (!empty($prevSiblingId)) {
            $prevSibling = $repo->find($prevSiblingId);
        }

        if (is_object($prevSibling)) {
            $repo->persistAsNextSiblingOf($category, $prevSibling);
        } else {
            $repo->persistAsFirstChildOf($category, $parent);
        }

        $this->getObjectManager()->flush();
    }

    /**
     * Check if a parent node is an ancestor of a child node
     *
     * @param CategoryInterface $parentNode
     * @param CategoryInterface $childNode
     *
     * @return bool
     *
     * // TODO: To put in repo, SQL format?
     */
    public function isAncestor(CategoryInterface $parentNode, CategoryInterface $childNode)
    {
        $childPath = $this->getEntityRepository()->getPath($childNode);
        //Removing last part of the path as it's the node itself
        //which cannot be is own ancestor
        array_pop($childPath);
        $childCount = 0;
        $parentFound = false;

        while ($childCount < count($childPath) && (!$parentFound)) {
            $parentFound = ($childPath[$childCount]->getId() === $parentNode->getId());
            $childCount++;
        }

        return $parentFound;
    }
}
