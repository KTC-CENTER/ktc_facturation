import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour la gestion des lignes de documents (proforma/facture)
 * 
 * Usage:
 * <div data-controller="document-items" 
 *      data-document-items-tax-rate-value="19.25"
 *      data-document-items-currency-value="FCFA">
 */
export default class extends Controller {
    static targets = ['items', 'template', 'totalHT', 'totalTVA', 'totalTTC', 'itemCount'];
    static values = {
        taxRate: { type: Number, default: 19.25 },
        currency: { type: String, default: 'FCFA' }
    };

    connect() {
        this.itemIndex = this.itemsTarget.children.length;
        this.calculateTotals();
    }

    // Ajouter une nouvelle ligne
    addItem(event) {
        event.preventDefault();
        
        const template = this.templateTarget.innerHTML;
        const newItem = template.replace(/__INDEX__/g, this.itemIndex);
        
        this.itemsTarget.insertAdjacentHTML('beforeend', newItem);
        this.itemIndex++;
        
        this.updateItemCount();
        this.calculateTotals();
        
        // Focus sur le premier champ de la nouvelle ligne
        const lastItem = this.itemsTarget.lastElementChild;
        const firstInput = lastItem.querySelector('input, select');
        if (firstInput) firstInput.focus();
    }

    // Supprimer une ligne
    removeItem(event) {
        event.preventDefault();
        
        const item = event.target.closest('[data-document-items-target="item"]') 
            || event.target.closest('.document-item');
        
        if (item && this.itemsTarget.children.length > 1) {
            item.classList.add('opacity-50', 'scale-95', 'transition-all', 'duration-200');
            setTimeout(() => {
                item.remove();
                this.updateItemCount();
                this.calculateTotals();
            }, 200);
        } else if (this.itemsTarget.children.length === 1) {
            alert('Vous devez avoir au moins une ligne.');
        }
    }

    // Calculer le total d'une ligne
    calculateLineTotal(event) {
        const item = event.target.closest('[data-document-items-target="item"]') 
            || event.target.closest('.document-item');
        
        if (!item) return;

        const quantity = parseFloat(item.querySelector('[data-field="quantity"]')?.value) || 0;
        const unitPrice = parseFloat(item.querySelector('[data-field="unitPrice"]')?.value) || 0;
        const lineTotal = quantity * unitPrice;
        
        const totalField = item.querySelector('[data-field="lineTotal"]');
        if (totalField) {
            totalField.value = lineTotal.toFixed(0);
            totalField.textContent = this.formatCurrency(lineTotal);
        }
        
        this.calculateTotals();
    }

    // Calculer tous les totaux
    calculateTotals() {
        let totalHT = 0;
        
        this.itemsTarget.querySelectorAll('.document-item, [data-document-items-target="item"]').forEach(item => {
            const quantity = parseFloat(item.querySelector('[data-field="quantity"]')?.value) || 0;
            const unitPrice = parseFloat(item.querySelector('[data-field="unitPrice"]')?.value) || 0;
            totalHT += quantity * unitPrice;
        });
        
        const totalTVA = totalHT * (this.taxRateValue / 100);
        const totalTTC = totalHT + totalTVA;
        
        if (this.hasTotalHTTarget) {
            this.totalHTTarget.textContent = this.formatCurrency(totalHT);
            this.totalHTTarget.dataset.value = totalHT;
        }
        
        if (this.hasTotalTVATarget) {
            this.totalTVATarget.textContent = this.formatCurrency(totalTVA);
            this.totalTVATarget.dataset.value = totalTVA;
        }
        
        if (this.hasTotalTTCTarget) {
            this.totalTTCTarget.textContent = this.formatCurrency(totalTTC);
            this.totalTTCTarget.dataset.value = totalTTC;
        }
    }

    // Mettre à jour le compteur de lignes
    updateItemCount() {
        if (this.hasItemCountTarget) {
            this.itemCountTarget.textContent = this.itemsTarget.children.length;
        }
    }

    // Formater un montant
    formatCurrency(amount) {
        const formatter = new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        return `${formatter.format(amount)} ${this.currencyValue}`;
    }

    // Charger un produit depuis le catalogue
    loadProduct(event) {
        const select = event.target;
        const productId = select.value;
        
        if (!productId) return;
        
        const item = select.closest('[data-document-items-target="item"]') 
            || select.closest('.document-item');
        
        // Récupérer les données du produit depuis les options
        const option = select.selectedOptions[0];
        if (option && option.dataset) {
            const designation = item.querySelector('[data-field="designation"]');
            const characteristics = item.querySelector('[data-field="characteristics"]');
            const unitPrice = item.querySelector('[data-field="unitPrice"]');
            
            if (designation) designation.value = option.dataset.name || option.text;
            if (characteristics) characteristics.value = option.dataset.characteristics || '';
            if (unitPrice) unitPrice.value = option.dataset.price || 0;
            
            this.calculateLineTotal({ target: select });
        }
    }

    // Dupliquer une ligne
    duplicateItem(event) {
        event.preventDefault();
        
        const item = event.target.closest('[data-document-items-target="item"]') 
            || event.target.closest('.document-item');
        
        if (!item) return;
        
        const clone = item.cloneNode(true);
        
        // Mettre à jour les index dans le clone
        clone.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${this.itemIndex}]`);
            input.id = input.id?.replace(/_\d+_/, `_${this.itemIndex}_`);
        });
        
        clone.querySelectorAll('[for]').forEach(label => {
            label.htmlFor = label.htmlFor?.replace(/_\d+_/, `_${this.itemIndex}_`);
        });
        
        this.itemsTarget.appendChild(clone);
        this.itemIndex++;
        
        this.updateItemCount();
        this.calculateTotals();
    }
}
