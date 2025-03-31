<?php

/**
 * Plugin Name: NOVI LSG Tool Box
 * Author: Lucas Troteseil
 * Description: Importe les polices personnalisÃ©es, crÃ©e des blocs gutenberg et champs personnalisÃ©s pour les fiches produit et gÃ¨re leur traductions, ajoute un script personnalisÃ© pour les input quantity, retire l'option "note de commande", gÃ¨re le mini diag, gÃ¨re les banniÃ¨res personnalisÃ©es des catÃ©gories ainsi que leurs champs personnalisÃ©s, scripts pour les animations et fonctionnalitÃ©s des menus
 * Version: 1.0.0
 */

// Ajoutez les hooks pour AJAX
//add_action('wp_ajax_update_progress_bar', 'update_progress_bar');
//add_action('wp_ajax_nopriv_update_progress_bar', 'update_progress_bar');

//add_action('woocommerce_after_cart_item_quantity_update', 'update_progress_bar');

// Fonction de rendu du bloc
function render_custom_freeshiping($attributes, $content)
{

?>

    <script type="text/javascript">
        function convertCurrencyStringToFloat(currencyString) {
            // Retirer les espaces et le symbole de l'euro
            let cleanedString = currencyString.replace('â‚¬', '').trim();

            // Remplacer la virgule par un point
            cleanedString = cleanedString.replace(',', '.');

            // Convertir le string en float
            let floatValue = parseFloat(cleanedString);

            return floatValue;
        }


        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour observer les changements du sous-total
            function observeSubTotal(element) {
                if (!element) return;

                const config = {
                    childList: true,
                    characterData: true,
                    subtree: true
                };
                const callback = function(mutationsList) {
                    for (const mutation of mutationsList) {
                        if (mutation.type === 'childList' || mutation.type === 'characterData') {
                            console.log('Sous-total changÃ© :', element.textContent);
                            // Placez ici le code pour mettre Ã  jour la barre de progression
                            updateProgressBar(element.textContent);
                        }
                    }
                };

                const observer = new MutationObserver(callback);
                observer.observe(element, config);
            }

            // Fonction pour vÃ©rifier pÃ©riodiquement la prÃ©sence de l'Ã©lÃ©ment sous-total
            function waitForSubTotalElement(container, timeout = 5000, interval = 100) {
                return new Promise((resolve, reject) => {
                    const startTime = Date.now();

                    const checkExist = setInterval(() => {
                        const subTotalElement = container.querySelector('.wc-block-components-totals-item__value');

                        if (subTotalElement) {
                            clearInterval(checkExist);
                            resolve(subTotalElement);
                        } else if (Date.now() - startTime > timeout) {
                            clearInterval(checkExist);
                            reject(new Error('Timeout waiting for sub-total element'));
                        }
                    }, interval);
                });
            }

            // Fonction pour dÃ©tecter la crÃ©ation et la suppression du mini panier
            function observeMiniCart() {
                const miniCartClass = 'wc-block-components-drawer__screen-overlay';
                const config = {
                    childList: true,
                    subtree: true
                };
                const callback = function(mutationsList) {
                    for (const mutation of mutationsList) {
                        for (const addedNode of mutation.addedNodes) {
                            if (addedNode.nodeType === 1 && addedNode.classList.contains(miniCartClass)) {
                                // VÃ©rifier pÃ©riodiquement la prÃ©sence de l'Ã©lÃ©ment sous-total
                                waitForSubTotalElement(addedNode).then(subTotalElement => {
                                    console.log('Mini panier crÃ©Ã©, sous-total :', subTotalElement);
                                    observeSubTotal(subTotalElement);
                                    updateProgressBar(subTotalElement.textContent);
                                }).catch(error => {
                                    console.error(error);
                                });
                            }
                        }
                        for (const removedNode of mutation.removedNodes) {
                            if (removedNode.nodeType === 1 && removedNode.classList.contains(miniCartClass)) {
                                console.log('Mini panier supprimÃ©');
                            }
                        }
                    }
                };

                const observer = new MutationObserver(callback);
                observer.observe(document.body, config);
            }

            // Fonction pour mettre Ã  jour la barre de progression
            function updateProgressBar(subTotalText) {
                if (!subTotalText) return;

                let text = document.getElementById('text-indicator');

                let goal = 40;
                let bar_max = goal + 26;

                let subTotal = convertCurrencyStringToFloat(subTotalText);
                let progress = Math.min(100, (subTotal / bar_max) * 100);
                let progressBarFill = document.querySelector('.progress-bar-fill');
                //let progressBarText = document.querySelector('.progress-bar-text');

                if (subTotal < goal) {
                    let calcul = (goal - subTotal).toFixed(2);
                    //text.innerHTML = "Plus que <strong>"+calcul+" â‚¬</strong> avant la livraison de votre commande offerte !";
                    text.innerHTML = "<?= __('Plus que', 'lsg_toolbox'); ?> <strong>" + calcul + " â‚¬</strong> <?= __(' pour la livraison gratuite !', 'lsg_toolbox'); ?>";
                } else if (subTotal >= goal) {
                    //text.innerHTML = "La livraison de votre commande est offerte !";
                    text.innerHTML = "<?= __('La livraison de votre commande est gratuite !', 'lsg_toolbox'); ?>";
                }

                if (progressBarFill && text) {
                    progressBarFill.style.width = progress + '%';
                    //progressBarText.textContent = subTotalText;
                } else {
                    console.error('Progress bar elements not found.');
                }
            }

            // Initialiser l'observation du mini panier
            observeMiniCart();
        });
        //
    </script>
    <?php
    ob_start();
    ?>

    <div class="pb-cont">
        <p id="text-indicator">Plus que <span class="progress-bar-text">0.00</span> avant une rÃ©duction.</p>
        <div class="pb">
            <div class="progress-bar-wrapper">
                <div class="progress-bar">
                    <div class="progress-bar-fill"></div>
                </div>
            </div>
            <div class="progress-bar-marker" style="left: 66.67%;"></div>
            <div class="progress-bar-marker-txt"><?= __('Livraison offerte', 'lsg_toolbox'); ?></div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}




function register_custom_freeshiping()
{
    register_block_type('custom/freeshiping', array(
        'render_callback' => 'render_custom_freeshiping'
    ));
}
add_action('init', 'register_custom_freeshiping');

// Enqueue block assets
function custom_block_assets()
{
    $file_path = plugin_dir_path(__FILE__) . 'block.js';

    if (file_exists($file_path)) {
        wp_enqueue_script(
            'custom-block-js',
            plugin_dir_url(__FILE__) . 'block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-data'),
            filemtime($file_path),
            true
        );
    } else {
        error_log('File block.js not found: ' . $file_path);
    }
}
add_action('enqueue_block_editor_assets', 'custom_block_assets');


function jozz_enqueue_styles() {
    wp_enqueue_style(
        'jozz-progressive-bar-css',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'assets/style.css') // Ajoute une version basée sur la modification du fichier
    );
}
add_action('wp_enqueue_scripts', 'jozz_enqueue_styles');





























?>
