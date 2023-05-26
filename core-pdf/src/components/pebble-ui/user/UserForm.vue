<template>
    <section class="text-center">

        <DisplayAvatar :userName="displayName" :lock="lockedDisplay('Avatar')" @edit-mode="updateDisplayStatus($event,'Avatar')"/>

<!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->

        <DisplayNameForm :mail="email" :displayName="displayName" :lock="lockedDisplay('Name')" @edit-mode="updateDisplayStatus($event,'Name')"/>

<!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->        
        <hr>
        <AuthProviderInfo :provider="provider" :mail="email" :provider-icon="'bi bi-google'" v-if="provider !== 'password'" />

<!-- ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->

        <ManagePassword :lock="lockedDisplay('Password')" @edit-mode="updateDisplayStatus($event,'Password')" v-else/>
        
    </section>

</template>

<script>

import AuthProviderInfo from './AuthProviderInfo.vue';
import DisplayNameForm from './DisplayNameForm.vue';
import ManagePassword from './ManagePassword.vue';
import DisplayAvatar from './DisplayAvatar.vue';

export default {

    data() {
        return {
            infoProvider: false,
            displayName: null,
            displayInEdition:null
        }
    },

    computed: {

        /**
         * Retourne le nom du provider utilisé par l'utilisateur via l'API firebase
         * 
         * @return {string}
         */
        provider() {
            return this.$app.firebase_user.providerData[0].providerId;
        },

        /**
         * Retourne l'email de connexion de l'utilisateur via l'API firebase
         *  
         * @return {string}
         */
        email(){
            return this.$app.firebase_user.email;
        },

    },
    methods: {

        /**
         * Retourne une valeur booléenne en fonction du nom de display ouvert(utilisé) afin de déterminer si il peut se deployer ou non 
         * 
         * @param {String} nameDisplay 
         * 
         * @returns {Boolean} 
         */
        lockedDisplay(nameDisplay){
            if(!this.displayInEdition)return false;
            return this.displayInEdition !== nameDisplay; 
        },

        /**
         * Modifie le nom du display qui est utilisé ou met la valeur à null afin de laisser la possibilité à tout les displays de s'ouvrir 
         * 
         * @param {Boolean} editStatus 
         * @param {String} nameDisplay 
         */
        updateDisplayStatus(editStatus, nameDisplay){
            if(editStatus){
                this.displayInEdition = nameDisplay;
            }
            else if(this.displayInEdition === nameDisplay && !editStatus){
                this.displayInEdition = null;
            }
        },

        /**
         * Retourne le display name stocké via l'API Firebase
         * 
         * @returns {String}
         */
         getName() {
            return this.$app.firebase_user.displayName;
        },
    },

    components: { AuthProviderInfo, DisplayNameForm, ManagePassword, DisplayAvatar },

    beforeUnmount() {
        this.$app.clearEventListener("display-name-updated");
    },

    mounted() {
        this.displayName = this.getName();

        this.$app.addEventListener("display-name-updated", () => {
            this.displayName = this.getName();
        });
    }
}

</script>