<template>
    <div class="text-center py-4" :class="classListSpinnerBox+' '+classList">
        <div :class="classListSpinner" role="status"></div>
        <div class="fs-4" v-if="computedLabel">{{computedLabel}}</div>
    </div>
</template>

<script>
/**
 * Affiche un spinner de chargement
 * 
 * @prop {String} type Classe bootstrap du spinner (ex : border pour spinner-border)
 * @prop {String} variant couleur bootstrap (ex : secondary)
 * @prop {String} classList liste de classes additionnelles à placer dans l'élément maitre
 * @prop {String} label Libellé sous le spinner
 */
export default {
    props: {
        type: {
            type: String,
            default: "border"
        },
        variant: {
            type: String,
            default: "secondary"
        },
        classList: String,
        message: String,
        label: {
            type: String,
            default: "Chargement..."
        }
    },

    computed: {
        computedLabel() {
            if (this.message) {
                return this.message;
            }
            else {
                return this.label;
            }
        },

        /**
         * retourn une liste de classe pour l'element du spinner en utilisant la propriété type.
         * @return {String}
         */
        classListSpinner() {
            let classList = "";

            if(this.type) {
                classList += " spinner-" + this.type;
            }

            return classList;
        },

        /**
         * retourn une liste de classes basée sur la propriété variant (danger, secondary...)
         * @return {String}
         */
        classListSpinnerBox() {
            let classList = "";
            
            if(this.variant) {
                classList += "text-" + this.variant;
            }

            return classList;
        }
    }
}
</script>