<template>
    <div class="bg-light border-bottom px-2 py-1 shadow-sm sticky-top pebble-header-toolbar" id="app-header-toolbar">
        <slot></slot>
    </div>
</template>

<script>
export default {
    methods: {
        /**
         * Gère les proportions pour l'affichage de la header toolabr
         */
        resize() {
            let header = document.getElementById('app-header-toolbar')
            let appHeader = document.getElementById('app-header');
            if (appHeader) {
                let top = appHeader.offsetHeight;
                header.style.top = top+'px';
            }
        }
    },

    mounted() {
        this.resize();

        document.addEventListener('resize', () => {
            this.resize();
        });
    }
}
</script>