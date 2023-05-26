<template>
    <button role="button" class="btn btn-primary mx-2" :class="cpBtnClass" @click.prevent="copyLink()">
        <i class="bi " :class="cpBtnIcon"></i>
        {{cpBtnLabel}}
    </button>
</template>

<script>

export default {
    props: {
        link: String
    },

    data() {
        return {
            clipboard: false
        } 
    },

    computed: {
        /**
         * Retourne le libellé du bouton de partage en fonction de l'état du clipboard
         * 
         * @return {string}
         */
         cpBtnLabel() {
            return this.clipboard ? "URL Copiée" : "Partager";
        },

        /**
         * Retourne la classe du bouton de partage en fonction de l'état du clipboard
         * 
         * @return {string}
         */
        cpBtnClass() {
            return this.clipboard ? "btn-success" : "btn-primary";
        },

        /**
         * Retourne l'icon du bouton de partage en fonction de l'état du clipboard
         * 
         * @return {string}
         */
        cpBtnIcon() {
            return this.clipboard ? "bi-check-lg" : "bi-box-arrow-up-right";
        }
    },

    methods: {
        /**
         * Copy l'url dans le clipboard et change le visuel du button avant de revenir a la normal
         */
        copyLink() {
            this.clipboard = true;

            navigator.clipboard.writeText(this.link);
    
            setTimeout(() => {
                this.clipboard=false;
            }, 1500);
        },
    }
}
</script>