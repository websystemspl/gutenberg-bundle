<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\EasyAdmin\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use WebSystems\GutenbergBundle\EasyAdmin\Field\GutenbergField;
use WebSystems\GutenbergBundle\Entity\ReusableBlock;

/**
 * Ready-made management screen for reusable blocks, registered automatically when both
 * EasyAdmin and Doctrine are installed.
 *
 * Extend it to translate the labels or to restrict access; applications with a hand-written
 * admin panel can ignore it and drive ReusableBlockRepository themselves.
 */
class ReusableBlockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ReusableBlock::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reusable block')
            ->setEntityLabelInPlural('Reusable blocks')
            ->setHelp('index', 'Content saved here can be inserted into any document; editing it updates every page that references it.')
            ->setDefaultSort(['updatedAt' => 'DESC'])
            ->setFormThemes([
                '@WebSystemsGutenberg/form/gutenberg_widget.html.twig',
                '@EasyAdmin/crud/form_theme.html.twig',
            ])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Title');
        yield SlugField::new('slug', 'Reference')
            ->setTargetFieldName('title')
            ->setHelp('Blocks can be referenced by this slug as well as by their numeric id.');
        yield GutenbergField::new('content', 'Content')
            ->setHeight(620)
            ->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Updated')->onlyOnIndex();
    }
}
