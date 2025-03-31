(function (blocks, element, data) {
    var el = element.createElement;
    var useBlockProps = blocks.useBlockProps;
    var withSelect = data.withSelect;

    function registerCustomBlock(blockName, title, fields) {
        blocks.registerBlockType(`custom/${blockName}`, {
            title: title,
            icon: 'info',
            category: 'common',
            attributes: fields.reduce((acc, field) => {
                acc[field.metaKey] = {
                    type: 'string',
                    source: 'meta',
                    meta: field.metaKey
                };
                return acc;
            }, {}),
            edit: withSelect(function (select) {
                var postMeta = select('core/editor').getEditedPostAttribute('meta');
                var values = fields.map(field => ({
                    key: field.metaKey,
                    value: postMeta[field.metaKey] || field.defaultValue // Utilisation d'une valeur par dÃ©faut si la mÃ©tadonnÃ©e est absente
                }));
            
                return function (props) {
                    var blockProps = useBlockProps();
                    return el('div', blockProps,
                        el('h4', { style: { color: 'grey' } }, title),
                        values.map(value =>
                            el('div', {},
                                el('h3', { style: { color: 'black' } }, fields.find(field => field.metaKey === value.key).label),
                                el('p', { style: { color: 'black' } }, value.value)
                            )
                        )
                    );
                };
            }),
            save: function() {
                return null; // Le contenu sera rendu par PHP
            }
        });
    }

    // Define the fields for each block
    const blocksData = [
           {
            blockName: 'freeshiping',
            title: 'Bloc barre de progression livraison gratuite',
            fields: [
                { metaKey: 'limit_price', label: 'Prix cible', defaultValue: 'def' }
            ]
        }

    ];

    // Register all custom blocks
    blocksData.forEach(blockData => {
        registerCustomBlock(blockData.blockName, blockData.title, blockData.fields);
    });

}(window.wp.blocks, window.wp.element, window.wp.data));

