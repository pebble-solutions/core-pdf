<template>
    <div class="progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" :style="pxWidth">
        <div class="progress-bar progress-bar-striped progress-bar-animated" :class="progressClass" style="width: 100%"></div>
    </div>
</template>

<script>

export default {
    props: {
        size: Number,
        progressClass: {
            type: String,
            default: 'bg-secondary opacity-25'
        }
    },

    computed: {
        /**
         * Retourne la largeur de l'élément en px
         * 
         * @return {string}
         */
        pxWidth() {
            return this.size ? `width:${this.size}px;` : '';
        }
    }
}

</script>