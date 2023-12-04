<?php

declare(strict_types=1);

use AnzuSystems\SerializerBundle\DependencyInjection\Configuration;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\NoNullPropertyInitializationFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationArrayAssignmentFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationBracesFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationIndentationFixer;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationSpacesFixer;
use PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocTagRenameFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAnnotationWithoutDotFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocInlineTagNormalizerFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use SlevomatCodingStandard\Sniffs\Classes\MethodSpacingSniff;
use SlevomatCodingStandard\Sniffs\Classes\ModernClassNameReferenceSniff;
use SlevomatCodingStandard\Sniffs\Classes\ParentCallSpacingSniff;
use SlevomatCodingStandard\Sniffs\Classes\PropertySpacingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\DocCommentSpacingSniff;
use SlevomatCodingStandard\Sniffs\Commenting\UselessInheritDocCommentSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\RequireNullCoalesceOperatorSniff;
use SlevomatCodingStandard\Sniffs\Functions\ArrowFunctionDeclarationSniff;
use SlevomatCodingStandard\Sniffs\Functions\StrictCallSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedInheritedVariablePassedToClosureSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseDoesNotStartWithBackslashSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseFromSameNamespaceSniff;
use SlevomatCodingStandard\Sniffs\Numbers\RequireNumericLiteralSeparatorSniff;
use SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\UselessConstantTypeHintSniff;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(SetList::CLEAN_CODE);
    $ecsConfig->import(SetList::PSR_12);
    $ecsConfig->import(SetList::COMMON);

    $ecsConfig->cacheDirectory(__DIR__ . '/var/ecs_cache');

    $ecsConfig->skip([
        Configuration::class,
        PhpdocTypesOrderFixer::class,
        ArrayListItemNewlineFixer::class => null,
        PhpdocToCommentFixer::class => null,
        PhpdocAlignFixer::class => null,
        ClassAttributesSeparationFixer::class => null,
        PhpdocInlineTagNormalizerFixer::class => null,
        GeneralPhpdocTagRenameFixer::class => null,
        SingleLineThrowFixer::class => null,
        ArrayOpenerAndCloserNewlineFixer::class => null,
        ParentCallSpacingSniff::class => null,
        DoctrineAnnotationBracesFixer::class => null,
        NotOperatorWithSuccessorSpaceFixer::class => null,
        UselessParenthesesSniff::class => null,
        PhpdocSeparationFixer::class => null, // some bug with infinity applied checker
        MethodChainingIndentationFixer::class => ['DependencyInjection/*Configuration.php'],
        'SlevomatCodingStandard\Sniffs\Whitespaces\DuplicateSpacesSniff.DuplicateDeclareStrictTypesSpaces' => null,
        'SlevomatCodingStandard\Sniffs\Commenting\DisallowCommentAfterCodeSniff.DisallowedCommentAfterCode' => null,
        PhpdocAnnotationWithoutDotFixer::class => null,
        PhpCsFixer\Fixer\Import\NoUnusedImportsFixer::class => null // bug: removes usages of attributes if attributes are in one line i.e. #[OARequest, OAResponse]
    ]);

    $ecsConfig->extend(NoSuperfluousPhpdocTagsFixer::class, function (NoSuperfluousPhpdocTagsFixer $noSuperfluousPhpdocTagsFixer) {
        $noSuperfluousPhpdocTagsFixer->configure(['remove_inheritdoc' => false, 'allow_mixed' => false]);
        return $noSuperfluousPhpdocTagsFixer;
    });

    $ecsConfig->extend(ClassDefinitionFixer::class, function (ClassDefinitionFixer $classDefinitionFixer) {
        $classDefinitionFixer->configure(['multi_line_extends_each_single_line' => false]);
        return $classDefinitionFixer;
    });

    $ecsConfig->extend(ClassAttributesSeparationFixer::class, function (ClassAttributesSeparationFixer $classAttributesSeparationFixer) {
        $classAttributesSeparationFixer->configure(['elements' => ['method', 'property']]);
        return $classAttributesSeparationFixer;
    });

    $ecsConfig->ruleWithConfiguration(DocCommentSpacingSniff::class, [
        'annotationsGroups' => [
            '@inheritDoc',
            '@template, @extends, @implements, @template-implements @template-extends',
            '@var, @psalm-var, @param, @psalm-param',
            '@return, @psalm-return',
            '@throws',
            '@psalm-suppress',
        ],
    ]);

    $ecsConfig->ruleWithConfiguration(UnusedUsesSniff::class, [
        'searchAnnotations' => true,
    ]);

    $ecsConfig->ruleWithConfiguration(PropertySpacingSniff::class, [
        'minLinesCountBeforeWithComment' => 1,
        'maxLinesCountBeforeWithComment' => 1,
        'maxLinesCountBeforeWithoutComment' => 0,
    ]);

    $ecsConfig->rule(AlphabeticallySortedUsesSniff::class);
    $ecsConfig->rule(StrictCallSniff::class);
    $ecsConfig->rule(DeclareStrictTypesFixer::class);
    $ecsConfig->rule(MethodSpacingSniff::class);
    $ecsConfig->rule(NoNullPropertyInitializationFixer::class);
    $ecsConfig->rule(ArrowFunctionDeclarationSniff::class);
    $ecsConfig->rule(UseDoesNotStartWithBackslashSniff::class);
    $ecsConfig->rule(RequireNumericLiteralSeparatorSniff::class);
    $ecsConfig->rule(UselessParenthesesSniff::class);
    $ecsConfig->rule(RequireNullCoalesceOperatorSniff::class);
    $ecsConfig->rule(ModernClassNameReferenceSniff::class);
    $ecsConfig->rule(UselessInheritDocCommentSniff::class);
    $ecsConfig->rule(UseFromSameNamespaceSniff::class);
    $ecsConfig->rule(UnusedInheritedVariablePassedToClosureSniff::class);
    $ecsConfig->rule(UselessConstantTypeHintSniff::class);
    $ecsConfig->rule(DoctrineAnnotationArrayAssignmentFixer::class);
    $ecsConfig->rule(DoctrineAnnotationIndentationFixer::class);
    $ecsConfig->rule(DoctrineAnnotationSpacesFixer::class);
    $ecsConfig->rule(BlankLineBeforeStatementFixer::class);

    $ecsConfig->ruleWithConfiguration(YodaStyleFixer::class, ['equal' => true, 'identical' => true, 'less_and_greater' => null]);

    $ecsConfig->ruleWithConfiguration(ForbiddenFunctionsSniff::class, [
        'forbiddenFunctions' => [
            'chop' => 'rtrim',
            'close' => 'closedir',
            'delete' => 'unset',
            'doubleval' => 'floatval',
            'fputs' => 'fwrite',
            'imap_create' => 'createmailbox',
            'imap_fetchtext' => 'body',
            'imap_header' => 'headerinfo',
            'imap_listmailbox' => 'list',
            'imap_listsubscribed' => 'lsub',
            'imap_rename' => 'renamemailbox',
            'imap_scan' => 'listscan',
            'imap_scanmailbox' => 'listscan',
            'mt_rand' => 'random_int',
            'ini_alter' => 'set',
            'is_double' => 'is_float',
            'is_integer' => 'is_int',
            'is_null' => '!== null',
            'is_real' => 'is_float',
            'is_writeable' => 'is_writable',
            'join' => 'implode',
            'key_exists' => 'array_key_exists',
            'magic_quotes_runtime' => 'set_magic_quotes_runtime',
            'pos' => 'current',
            'rand' => 'random_int',
            'show_source' => 'file',
            'sizeof' => 'count',
            'strchr' => 'strstr',
            'create_function' => null,
            'call_user_func' => null,
            'call_user_func_array' => null,
            'forward_static_call' => null,
            'forward_static_call_array' => null,
            'dump' => null,
            'die' => null,
            'exit' => null,
            'var_dump' => null,
        ],
    ]);

    $ecsConfig->extend(ArraySyntaxFixer::class, function (ArraySyntaxFixer $arraySyntaxFixer) {
        $arraySyntaxFixer->configure(['syntax' => 'short']);
        return $arraySyntaxFixer;
    });

    $ecsConfig->ruleWithConfiguration(ListSyntaxFixer::class, [
        'syntax' => 'short',
    ]);

};
