<template>
    <a :href="cfgMenu.href" class="dropdown-item" @click.prevent="configModule()" v-if="cfgMenu.href">Configuration du module</a>
    <!--<a href="/mkg/modules/parametre/private/php/index.php" class="dropdown-item" target="parametreApp">Configuration générale</a>-->
    <button class="dropdown-item" @click.prevent="storageModal()">Data viewer</button>
</template>


<script>
export default {
    props: {
        cfgMenu: {
            type: Object,
            default() { return { href: null } }
        }
    },

    emits: ['config-module', 'storage-modal'],

    methods: {
        /**
         * Envoie un événement 'config-module' à l'élément parent
         */
        configModule() {
            this.$emit('config-module');
        },

        /**
         * Envoie un événement 'storage-modal' à l'élément parent
         */
        storageModal() {
            this.$emit('storage-modal');
        }
    }
}
</script>
