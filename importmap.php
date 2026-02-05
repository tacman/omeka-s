<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'lightgallery' => [
        'version' => '2.9.0',
    ],
    'chosen-js' => [
        'version' => '1.8.7',
    ],
    'compare-versions' => [
        'version' => '6.1.1',
    ],
    'jquery' => [
        'version' => '4.0.0',
    ],
    'jstree' => [
        'version' => '3.3.17',
    ],
    'mirador' => [
        'version' => '4.0.0',
    ],
    'react/jsx-runtime' => [
        'version' => '19.2.0',
    ],
    'react-dom/client' => [
        'version' => '19.2.0',
    ],
    'react' => [
        'version' => '19.2.0',
    ],
    '@mui/material/Paper' => [
        'version' => '7.3.4',
    ],
    'react-i18next' => [
        'version' => '15.7.4',
    ],
    '@mui/material/styles' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Typography' => [
        'version' => '7.3.4',
    ],
    '@mui/material/utils' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Badge' => [
        'version' => '7.3.4',
    ],
    '@mui/material/IconButton' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Tooltip' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Chip' => [
        'version' => '7.3.4',
    ],
    '@mui/material/MenuList' => [
        'version' => '7.3.4',
    ],
    '@mui/material/MenuItem' => [
        'version' => '7.3.4',
    ],
    '@mui/material/ListItemText' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Toolbar' => [
        'version' => '7.3.4',
    ],
    'react-dom' => [
        'version' => '19.2.0',
    ],
    '@mui/material/Accordion' => [
        'version' => '7.3.4',
    ],
    '@mui/material/AccordionDetails' => [
        'version' => '7.3.4',
    ],
    '@mui/material/AccordionSummary' => [
        'version' => '7.3.4',
    ],
    '@mui/material/InputLabel' => [
        'version' => '7.3.4',
    ],
    '@mui/material/FormControl' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Select' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Button' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Link' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Tabs' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Tab' => [
        'version' => '7.3.4',
    ],
    '@mui/system/RtlProvider' => [
        'version' => '7.3.3',
    ],
    '@mui/material/Collapse' => [
        'version' => '7.3.4',
    ],
    '@mui/system/createStyled' => [
        'version' => '7.3.3',
    ],
    '@mui/material/Checkbox' => [
        'version' => '7.3.4',
    ],
    '@mui/system' => [
        'version' => '7.3.3',
    ],
    '@mui/material/Skeleton' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Autocomplete' => [
        'version' => '7.3.4',
    ],
    '@mui/material/CircularProgress' => [
        'version' => '7.3.4',
    ],
    '@mui/material/TextField' => [
        'version' => '7.3.4',
    ],
    '@mui/material/InputAdornment' => [
        'version' => '7.3.4',
    ],
    '@mui/material/List' => [
        'version' => '7.3.4',
    ],
    '@mui/material/ListItem' => [
        'version' => '7.3.4',
    ],
    '@mui/material/ListItemButton' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Input' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Slider' => [
        'version' => '7.3.4',
    ],
    '@mui/material/ListItemIcon' => [
        'version' => '7.3.4',
    ],
    '@emotion/react' => [
        'version' => '11.14.0',
    ],
    '@mui/material/SvgIcon' => [
        'version' => '7.3.4',
    ],
    '@mui/material/AppBar' => [
        'version' => '7.3.4',
    ],
    '@mui/material/ListSubheader' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Popover' => [
        'version' => '7.3.4',
    ],
    '@mui/material/FormControlLabel' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Menu' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Drawer' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Slide' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Stack' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Alert' => [
        'version' => '7.3.4',
    ],
    '@mui/material' => [
        'version' => '7.3.4',
    ],
    '@mui/material/DialogContent' => [
        'version' => '7.3.4',
    ],
    '@mui/material/DialogActions' => [
        'version' => '7.3.4',
    ],
    '@mui/material/DialogTitle' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Snackbar' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Dialog' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Backdrop' => [
        'version' => '7.3.4',
    ],
    '@mui/material/colors' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Grid' => [
        'version' => '7.3.4',
    ],
    '@mui/material/ButtonBase' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Divider' => [
        'version' => '7.3.4',
    ],
    '@mui/material/GlobalStyles' => [
        'version' => '7.3.4',
    ],
    '@mui/material/Fab' => [
        'version' => '7.3.4',
    ],
    '@mui/material/useMediaQuery' => [
        'version' => '7.3.4',
    ],
    'scheduler' => [
        'version' => '0.27.0',
    ],
    'prop-types' => [
        'version' => '15.8.1',
    ],
    'clsx' => [
        'version' => '2.1.1',
    ],
    '@mui/utils/integerPropType' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/chainPropTypes' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/composeClasses' => [
        'version' => '7.3.3',
    ],
    '@mui/system/colorManipulator' => [
        'version' => '7.3.3',
    ],
    '@mui/system/styleFunctionSx' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/formatMuiErrorMessage' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/deepmerge' => [
        'version' => '7.3.3',
    ],
    '@mui/system/spacing' => [
        'version' => '7.3.3',
    ],
    '@mui/system/cssVars' => [
        'version' => '7.3.3',
    ],
    '@mui/system/createTheme' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/generateUtilityClass' => [
        'version' => '7.3.3',
    ],
    '@mui/system/DefaultPropsProvider' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/generateUtilityClasses' => [
        'version' => '7.3.3',
    ],
    'html-parse-stringify' => [
        'version' => '3.0.1',
    ],
    '@mui/system/createBreakpoints' => [
        'version' => '7.3.3',
    ],
    '@mui/system/useThemeProps' => [
        'version' => '7.3.3',
    ],
    '@mui/system/InitColorSchemeScript' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/capitalize' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/createChainedFunction' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/debounce' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/deprecatedPropType' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/isMuiElement' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/ownerDocument' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/ownerWindow' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/requirePropFactory' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/setRef' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/useEnhancedEffect' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/useId' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/unsupportedProp' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/useControlled' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/useEventCallback' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/useForkRef' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/ClassNameGenerator' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/usePreviousProps' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/appendOwnerState' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/resolveComponentProps' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/mergeSlotProps' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/refType' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/elementTypeAcceptingRef' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/isFocusVisible' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/useLazyRef' => [
        'version' => '7.3.3',
    ],
    'react-transition-group' => [
        'version' => '4.4.5',
    ],
    '@mui/utils/useTimeout' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/elementAcceptingRef' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/getReactElementRef' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/HTMLElementType' => [
        'version' => '7.3.3',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    '@mui/utils/useSlotProps' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/exactProp' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/getScrollbarSize' => [
        'version' => '7.3.3',
    ],
    'react-is' => [
        'version' => '19.2.0',
    ],
    '@mui/utils/isHostComponent' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/extractEventHandlers' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/resolveProps' => [
        'version' => '7.3.3',
    ],
    '@mui/system/style' => [
        'version' => '7.3.3',
    ],
    '@mui/styled-engine' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/getDisplayName' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/clamp' => [
        'version' => '7.3.3',
    ],
    '@mui/private-theming' => [
        'version' => '7.3.3',
    ],
    '@mui/utils/visuallyHidden' => [
        'version' => '7.3.3',
    ],
    '@emotion/cache' => [
        'version' => '11.14.0',
    ],
    '@babel/runtime/helpers/esm/extends' => [
        'version' => '7.26.0',
    ],
    '@emotion/weak-memoize' => [
        'version' => '0.4.0',
    ],
    'hoist-non-react-statics' => [
        'version' => '3.3.2',
    ],
    '@emotion/utils' => [
        'version' => '1.4.2',
    ],
    '@emotion/serialize' => [
        'version' => '1.3.3',
    ],
    '@emotion/use-insertion-effect-with-fallbacks' => [
        'version' => '1.2.0',
    ],
    '@babel/runtime/helpers/extends' => [
        'version' => '7.26.0',
    ],
    '@mui/utils/getValidReactChildren' => [
        'version' => '7.3.3',
    ],
    '@mui/system/Grid' => [
        'version' => '7.3.3',
    ],
    '@mui/system/useMediaQuery' => [
        'version' => '7.3.3',
    ],
    'void-elements' => [
        'version' => '3.1.0',
    ],
    '@babel/runtime/helpers/esm/objectWithoutPropertiesLoose' => [
        'version' => '7.18.9',
    ],
    '@babel/runtime/helpers/esm/inheritsLoose' => [
        'version' => '7.18.9',
    ],
    'dom-helpers/addClass' => [
        'version' => '5.2.1',
    ],
    'dom-helpers/removeClass' => [
        'version' => '5.2.1',
    ],
    '@babel/runtime/helpers/esm/assertThisInitialized' => [
        'version' => '7.18.9',
    ],
    '@emotion/styled' => [
        'version' => '11.14.1',
    ],
    '@emotion/sheet' => [
        'version' => '1.4.0',
    ],
    'stylis' => [
        'version' => '4.2.0',
    ],
    '@emotion/memoize' => [
        'version' => '0.9.0',
    ],
    '@emotion/hash' => [
        'version' => '0.9.2',
    ],
    '@emotion/unitless' => [
        'version' => '0.10.0',
    ],
    '@emotion/is-prop-valid' => [
        'version' => '1.3.1',
    ],
    'openseadragon' => [
        'version' => '5.0.1',
    ],
    'sortablejs' => [
        'version' => '1.15.6',
    ],
    'tablesaw' => [
        'version' => '3.1.2',
    ],
    'shoestring' => [
        'version' => '2.0.1',
    ],
    'lightgallery/css/lightgallery.css' => [
        'version' => '2.9.0',
        'type' => 'css',
    ],
    'lightgallery/css/lg-thumbnail.css' => [
        'version' => '2.9.0',
        'type' => 'css',
    ],
    'lightgallery/css/lg-zoom.css' => [
        'version' => '2.9.0',
        'type' => 'css',
    ],
    'intl-messageformat' => [
        'version' => '10.7.18',
    ],
    'tslib' => [
        'version' => '2.8.1',
    ],
    '@formatjs/fast-memoize' => [
        'version' => '2.2.7',
    ],
    '@formatjs/icu-messageformat-parser' => [
        'version' => '2.11.4',
    ],
    '@formatjs/icu-skeleton-parser' => [
        'version' => '1.8.16',
    ],
    '@symfony/ux-translator' => [
        'path' => './vendor/symfony/ux-translator/assets/dist/translator_controller.js',
    ],
    'tom-select' => [
        'version' => '2.4.5',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.5',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.default.css' => [
        'version' => '2.4.5',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.bootstrap4.css' => [
        'version' => '2.4.5',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.5',
        'type' => 'css',
    ],
];
