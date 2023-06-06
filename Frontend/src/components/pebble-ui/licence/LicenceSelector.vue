<template>
    <div class="lead text-center">
        Vous disposez de {{licences.length}} licences pour cette application.
    </div>
    <div class="text-center">
        Sélectionnez la licence que vous souhaitez utiliser.
    </div>
    <div class="list-group list-group-flush mt-4 mb-4">
        <LicenceItem v-for="licence in licences" :key="licence._id" :licence="licence" @select-licence="selectLicence" />
    </div>

    <div class="text-center mt-3">
        <button class="btn btn-outline-secondary" @click.prevent="logout()">Changer de session <i class="bi bi-box-arrow-right ms-2"></i></button>
    </div>
</template>

<script>
import LicenceItem from "./LicenceItem.vue";

export default {
    props: {
        licences: Array
    },

    emits: ['error'],

    components: { LicenceItem },

    methods: {
        /**
         * Intègre la licence à sélectioner au niveau de l'application
         * @param {Object} licence La licence à sélectionner
         */
        selectLicence(licence) {
            this.$app.toggleLicence(licence);
        },

        /**
         * Ferme la session
         */
        logout() {
            this.$app.logout();
        }
    }
}

</script>