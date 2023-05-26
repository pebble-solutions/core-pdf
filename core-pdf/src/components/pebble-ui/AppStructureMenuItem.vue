<template>
    <button class="dropdown-item d-flex align-items-center justify-content-between" @click.prevent="selectStructure(structure.id)">
        {{structure.nom_interne}}
        <i v-if="structure.id === active_structure_id" class="bi bi-check2 text-success"></i>
    </button>
</template>

<script>

export default {
    props: {
        structure: Object,
        active_structure_id: Number
    },

    methods: {
        /**
         * Envoie un événement à l'élément parent pour modifier la structure sélectionnée
         * @param {Number} structureId L'ID de la structure à sélectionner
         */
        selectStructure(structureId) {
            this.$emit('select-structure', structureId);
        }
    }
}

</script>