// KTC Invoice Pro - Proforma/Invoice Management JavaScript
import TomSelect from 'tom-select';

// État du document
let documentState = {
    items: [],
    subtotal: 0,
    taxRate: 0,
    taxAmount: 0,
    total: 0
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initTemplateSelector();
    initProductSelector();
    initItemsTable();
    initCalculations();
    initFormValidation();
});

// Sélecteur de modèle préconfiguré
function initTemplateSelector() {
    const templateSelect = document.getElementById('templateSelect');
    if (!templateSelect) return;
    
    templateSelect.addEventListener('change', async function() {
        const templateId = this.value;
        if (!templateId) return;
        
        // Confirmation si des items existent déjà
        if (documentState.items.length > 0) {
            const result = await KTC.confirm({
                title: 'Charger le modèle ?',
                text: 'Les éléments actuels seront remplacés par ceux du modèle.',
                confirmText: 'Oui, charger'
            });
            
            if (!result.isConfirmed) {
                this.value = '';
                return;
            }
        }
        
        // Charger le modèle via AJAX
        try {
            const response = await fetch(`/api/templates/${templateId}/items`);
            const data = await response.json();
            
            // Vider le tableau
            documentState.items = [];
            
            // Ajouter les items du modèle
            data.items.forEach(item => {
                addItemToTable({
                    productId: item.product_id,
                    designation: item.designation,
                    characteristics: item.characteristics,
                    quantity: item.quantity,
                    unitPrice: item.unit_price,
                    groupName: item.group_name,
                    groupPrice: item.group_price
                });
            });
            
            // Appliquer les notes par défaut
            if (data.default_notes) {
                const notesField = document.getElementById('proforma_notes');
                if (notesField && !notesField.value) {
                    notesField.value = data.default_notes;
                }
            }
            
            updateTotals();
            KTC.success('Modèle chargé avec succès');
            
        } catch (error) {
            console.error('Erreur:', error);
            KTC.error('Erreur lors du chargement du modèle');
        }
    });
}

// Sélecteur de produit avec recherche
function initProductSelector() {
    const productSelect = document.getElementById('productSelect');
    if (!productSelect) return;
    
    new TomSelect(productSelect, {
        valueField: 'id',
        labelField: 'name',
        searchField: ['name', 'characteristics'],
        load: function(query, callback) {
            if (!query.length) return callback();
            
            fetch(`/api/products/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => callback(data))
                .catch(() => callback());
        },
        render: {
            option: function(data, escape) {
                return `<div class="py-2">
                    <div class="font-medium">${escape(data.name)}</div>
                    <div class="text-xs text-gray-500">${escape(data.characteristics || '')}</div>
                    <div class="text-xs font-semibold text-primary-600">${formatMoney(data.unit_price)}</div>
                </div>`;
            },
            item: function(data, escape) {
                return `<div>${escape(data.name)}</div>`;
            }
        },
        onChange: function(productId) {
            if (!productId) return;
            
            const product = this.options[productId];
            if (product) {
                // Pré-remplir les champs
                document.getElementById('itemDesignation').value = product.name;
                document.getElementById('itemCharacteristics').value = product.characteristics || '';
                document.getElementById('itemUnitPrice').value = product.unit_price;
                document.getElementById('itemQuantity').value = 1;
                document.getElementById('itemQuantity').focus();
            }
        }
    });
}

// Gestion du tableau des items
function initItemsTable() {
    const addItemBtn = document.getElementById('addItemBtn');
    const itemsTableBody = document.getElementById('itemsTableBody');
    
    if (!addItemBtn || !itemsTableBody) return;
    
    // Ajouter un item
    addItemBtn.addEventListener('click', function() {
        const productSelect = document.getElementById('productSelect');
        const designation = document.getElementById('itemDesignation').value.trim();
        const characteristics = document.getElementById('itemCharacteristics').value.trim();
        const quantity = parseInt(document.getElementById('itemQuantity').value) || 1;
        const unitPrice = parseFloat(document.getElementById('itemUnitPrice').value) || 0;
        
        if (!designation) {
            KTC.warning('Veuillez saisir une désignation');
            document.getElementById('itemDesignation').focus();
            return;
        }
        
        addItemToTable({
            productId: productSelect?.tomselect?.getValue() || null,
            designation,
            characteristics,
            quantity,
            unitPrice
        });
        
        // Réinitialiser les champs
        if (productSelect?.tomselect) productSelect.tomselect.clear();
        document.getElementById('itemDesignation').value = '';
        document.getElementById('itemCharacteristics').value = '';
        document.getElementById('itemQuantity').value = 1;
        document.getElementById('itemUnitPrice').value = '';
        document.getElementById('itemDesignation').focus();
        
        updateTotals();
    });
    
    // Entrée pour ajouter rapidement
    ['itemDesignation', 'itemCharacteristics', 'itemQuantity', 'itemUnitPrice'].forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addItemBtn.click();
                }
            });
        }
    });
    
    // Délégation d'événements pour les actions sur les items
    itemsTableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        
        const row = btn.closest('tr');
        const index = parseInt(row.dataset.index);
        
        if (btn.dataset.action === 'delete') {
            row.classList.add('bg-danger-50');
            setTimeout(() => {
                documentState.items.splice(index, 1);
                renderItemsTable();
                updateTotals();
            }, 150);
        }
        
        if (btn.dataset.action === 'edit') {
            editItem(index);
        }
    });
    
    // Édition en ligne des quantités et prix
    itemsTableBody.addEventListener('change', function(e) {
        const input = e.target;
        if (!input.matches('input')) return;
        
        const row = input.closest('tr');
        const index = parseInt(row.dataset.index);
        
        if (input.name.includes('quantity')) {
            documentState.items[index].quantity = parseInt(input.value) || 1;
        }
        
        if (input.name.includes('unitPrice')) {
            documentState.items[index].unitPrice = parseFloat(input.value) || 0;
        }
        
        updateRowTotal(row, index);
        updateTotals();
    });
}

// Ajouter un item au tableau
function addItemToTable(item) {
    documentState.items.push({
        productId: item.productId,
        designation: item.designation,
        characteristics: item.characteristics,
        quantity: item.quantity,
        unitPrice: item.unitPrice,
        groupName: item.groupName || null,
        groupPrice: item.groupPrice || null,
        total: item.quantity * item.unitPrice
    });
    
    renderItemsTable();
}

// Rendre le tableau des items
function renderItemsTable() {
    const tbody = document.getElementById('itemsTableBody');
    if (!tbody) return;
    
    if (documentState.items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Aucun élément ajouté. Sélectionnez un produit ou saisissez une désignation.
                </td>
            </tr>`;
        return;
    }
    
    let currentGroup = null;
    let html = '';
    
    documentState.items.forEach((item, index) => {
        // Afficher l'en-tête de groupe si nécessaire
        if (item.groupName && item.groupName !== currentGroup) {
            currentGroup = item.groupName;
            html += `
                <tr class="bg-primary-50">
                    <td colspan="5" class="px-4 py-2 font-semibold text-primary-700">
                        ${escapeHtml(item.groupName)}
                    </td>
                    <td class="px-4 py-2 text-right font-semibold text-primary-700">
                        ${item.groupPrice ? formatMoney(item.groupPrice) : ''}
                    </td>
                </tr>`;
        }
        
        const total = item.quantity * item.unitPrice;
        
        html += `
            <tr data-index="${index}" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">${escapeHtml(item.designation)}</div>
                    ${item.characteristics ? `<div class="text-xs text-gray-500 mt-0.5">${escapeHtml(item.characteristics)}</div>` : ''}
                    <input type="hidden" name="items[${index}][product_id]" value="${item.productId || ''}">
                    <input type="hidden" name="items[${index}][designation]" value="${escapeHtml(item.designation)}">
                    <input type="hidden" name="items[${index}][characteristics]" value="${escapeHtml(item.characteristics || '')}">
                </td>
                <td class="px-4 py-3 w-24">
                    <input type="number" name="items[${index}][quantity]" value="${item.quantity}" 
                           min="1" class="form-input text-center w-20 py-1.5">
                </td>
                <td class="px-4 py-3 w-40">
                    <input type="number" name="items[${index}][unit_price]" value="${item.unitPrice}" 
                           min="0" step="1" class="form-input text-right w-32 py-1.5">
                </td>
                <td class="px-4 py-3 text-right font-medium text-gray-900 item-total">
                    ${formatMoney(total)}
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" data-action="delete" class="text-danger-500 hover:text-danger-700 p-1" title="Supprimer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </td>
            </tr>`;
    });
    
    tbody.innerHTML = html;
}

// Mettre à jour le total d'une ligne
function updateRowTotal(row, index) {
    const item = documentState.items[index];
    item.total = item.quantity * item.unitPrice;
    
    const totalCell = row.querySelector('.item-total');
    if (totalCell) {
        totalCell.textContent = formatMoney(item.total);
    }
}

// Calculs des totaux
function initCalculations() {
    const taxRateInput = document.getElementById('taxRate');
    if (taxRateInput) {
        taxRateInput.addEventListener('change', updateTotals);
    }
}

function updateTotals() {
    // Calculer le sous-total
    documentState.subtotal = documentState.items.reduce((sum, item) => {
        return sum + (item.quantity * item.unitPrice);
    }, 0);
    
    // Récupérer le taux de TVA
    const taxRateInput = document.getElementById('taxRate');
    documentState.taxRate = taxRateInput ? parseFloat(taxRateInput.value) || 0 : 0;
    
    // Calculer la TVA
    documentState.taxAmount = documentState.subtotal * (documentState.taxRate / 100);
    
    // Calculer le total TTC
    documentState.total = documentState.subtotal + documentState.taxAmount;
    
    // Mettre à jour l'affichage
    const subtotalEl = document.getElementById('subtotal');
    const taxAmountEl = document.getElementById('taxAmount');
    const totalEl = document.getElementById('total');
    const totalWordsEl = document.getElementById('totalWords');
    
    if (subtotalEl) subtotalEl.textContent = formatMoney(documentState.subtotal);
    if (taxAmountEl) taxAmountEl.textContent = formatMoney(documentState.taxAmount);
    if (totalEl) totalEl.textContent = formatMoney(documentState.total);
    if (totalWordsEl) totalWordsEl.textContent = numberToWords(documentState.total) + ' FCFA';
    
    // Mettre à jour les champs cachés
    updateHiddenFields();
}

function updateHiddenFields() {
    const fields = {
        'proforma_totalHT': documentState.subtotal,
        'proforma_taxAmount': documentState.taxAmount,
        'proforma_totalTTC': documentState.total
    };
    
    Object.entries(fields).forEach(([id, value]) => {
        const field = document.getElementById(id);
        if (field) field.value = value;
    });
}

// Validation du formulaire
function initFormValidation() {
    const form = document.getElementById('proformaForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        if (documentState.items.length === 0) {
            e.preventDefault();
            KTC.warning('Veuillez ajouter au moins un élément');
            return false;
        }
        
        const clientSelect = document.getElementById('proforma_client');
        if (clientSelect && !clientSelect.value) {
            e.preventDefault();
            KTC.warning('Veuillez sélectionner un client');
            clientSelect.focus();
            return false;
        }
        
        return true;
    });
}

// Utilitaires
function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function numberToWords(num) {
    // Conversion simplifiée en français
    const units = ['', 'Un', 'Deux', 'Trois', 'Quatre', 'Cinq', 'Six', 'Sept', 'Huit', 'Neuf'];
    const teens = ['Dix', 'Onze', 'Douze', 'Treize', 'Quatorze', 'Quinze', 'Seize', 'Dix-sept', 'Dix-huit', 'Dix-neuf'];
    const tens = ['', 'Dix', 'Vingt', 'Trente', 'Quarante', 'Cinquante', 'Soixante', 'Soixante-dix', 'Quatre-vingt', 'Quatre-vingt-dix'];
    
    if (num === 0) return 'Zéro';
    
    let result = '';
    
    // Millions
    if (num >= 1000000) {
        const millions = Math.floor(num / 1000000);
        result += (millions === 1 ? 'Un Million ' : numberToWords(millions) + ' Millions ');
        num %= 1000000;
    }
    
    // Milliers
    if (num >= 1000) {
        const thousands = Math.floor(num / 1000);
        result += (thousands === 1 ? 'Mille ' : numberToWords(thousands) + ' Mille ');
        num %= 1000;
    }
    
    // Centaines
    if (num >= 100) {
        const hundreds = Math.floor(num / 100);
        result += (hundreds === 1 ? 'Cent ' : units[hundreds] + ' Cent ');
        num %= 100;
    }
    
    // Dizaines et unités
    if (num >= 10 && num < 20) {
        result += teens[num - 10];
    } else if (num >= 20 || num < 10) {
        if (num >= 20) {
            result += tens[Math.floor(num / 10)] + ' ';
            num %= 10;
        }
        if (num > 0) {
            result += units[num];
        }
    }
    
    return result.trim();
}

// Export
export { documentState, addItemToTable, updateTotals };
