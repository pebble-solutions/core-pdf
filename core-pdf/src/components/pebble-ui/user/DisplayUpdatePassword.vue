<template>
	<div class="py-2">
        <div class="d-flex flex-column" style="letter-spacing: 1px;">
            <input class="form-control" type="password" placeholder="Nouveau mot de passe" autocomplete="off" v-model="newPassword">

                <div class="p-3 m-4 mt-2 text-start alert" :class="{ 'alert-light': warningId, 'alert-danger border border-danger': !warningId }" role="alert" >
                    <div v-for="error in passwordValidation.errors" :key="error" :class="{ 'bi bi-check-circle text-success ': error.succesId==true, 'bi bi-x-circle text-danger  ': !error.succesId }">{{error.message}}</div>
                </div>

            <input class="form-control" type="password" placeholder="Confirmation mot de passe" v-model.lazy='checkPassword' autocomplete="off" @keydown.enter.prevent="verifNewPassword()">
        </div>	
        <button class="btn btn-outline-primary mt-4 m-2 " type="button" @click="verifNewPassword()" :disabled="pending.newPassword"> Valider
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="pending.newPassword"></span>
            <i class="bi bi-check" v-else></i>
        </button>			
        <button class="btn btn-outline-secondary bi bi-x mt-4 m-2 " type="button" @click="resetDisplay()"> Annuler </button>
    </div>				
    <div class="alert alert-warning italic" role="alert" v-if='newPasswordId==true'>  {{newPasswordError}}</div>
</template>


<script>
import {getAuth, reauthenticateWithCredential, EmailAuthProvider, updatePassword } from '@firebase/auth';

export default {
    data() {
        return {
            rules: [
                { message: "  Une lettre minuscule.", regex: /[a-z]+/ ,succesId:false},
                { message: "  Une lettre majuscule.", regex: /[A-Z]+/ ,succesId:false},
                { message: "  8 characteres minimum.", regex: /.{8,}/ ,succesId:false},
                { message: "  Un chiffre minimum.", regex: /[0-9]+/ ,succesId:false}
            ],
            newPassword: "",
            newPasswordError: "",
            newPasswordId: false,
            warningId: true,
            checkAcceptId: true,
            checkPassword: "",
            pending: {
                updatePassword: false,
                displayNewPassword: false
            },
            success: false
        };
    },
    emits:["step-change"],
    methods: {
        /**
         * Animation d'erreur de complexité sur le mot de passe
         */
        activateWarning() {
            this.warningId=!this.warningId;
            setTimeout(() => this.warningId = !this.warningId, 600);
        },

        /**
         * Verifie si le mot de passe est bien le mot de passe du login de firebase
         * 
         * @returns {Promise<UserCredential>}
         */
        reauth() {
            let cred = EmailAuthProvider.credential(getAuth().currentUser.email, this.oldPassword);
            return reauthenticateWithCredential(getAuth().currentUser, cred)
                .then((userCred) => {
                return userCred;
            });
        },
        
        /**
         * Reinitialise toutes les valeurs afin que aucune pop-up de default sois visible et que chaque displayPassword sois remis àzero à la saisie
         */
        resetDisplay() {
            this.newPassword = "";
            this.checkPassword = "";
            this.newPasswordId = false;
            this.$emit("step-change",0);
        },
        /**
         * Determine si le mot de passe taper est accepter en terme de complexité grâce au regex determionés dans les rules
         * 
         * @returns {Boolean}
         */
        passValid() {
            let errors = [];
            for (let condition of this.rules) {
                if (!condition.regex.test(this.newPassword)) {
                    errors.push("Erreur");
                }
            }

            if (errors.length === 0) {
                return  true;
            }
            else {
                return false;
            }
        },
        
        /**
         * Verifie si le nouveau mot de passe entrer est accepter :
         * - Selon la complexite,
         * - Si ce n'est pas une chaine vide
         * - Si le nouveau mot de passe et le 'checkPassword' sont identique
         * 
         * - Sinon renvoie des messages d'erreur sous forme de pop-up (grâce à newPasswordId)
         */
        verifNewPassword() {
            if (this.pending.displayNewPassword)
                return;
            this.pending.displayNewPassword = true;
            if (this.passwordsFilled == false) {
                this.newPasswordError = "Champs vide, veuillez entrer une valeur correspondante";
                this.newPasswordId = true;
                this.pending.displayNewPassword = false;
            }
            else if (this.passValid() == false) {
                //this.warningId=!this.warningId;
                this.pending.displayNewPassword = false;
                this.activateWarning();
            }
            else if (this.notSamePasswords) {
                this.newPasswordError = "Les mots de passe sont différents, veuillez réessayer";
                this.newPasswordId = true;
                this.pending.displayNewPassword = false;
            }
            else {
                this.upPassword();
            }
        },

        /**
         * Modifie le mot de passe sur l'API firebase
         */
        upPassword() {
            this.pending.updatePassword = true;
            let auth = getAuth();
            let user = auth.currentUser;
            const cred = EmailAuthProvider.credential(user.email, this.oldPassword);
            //user.reauthenticateWithCredential(cred)
            reauthenticateWithCredential(user, cred)
                .then(() => {
                updatePassword(user, this.newPassword);
            })
                .then(() => {
                this.success = true;
                this.resetDisplay();
            })
                .catch(this.$app.catchError)
                .finally(() => {
                this.pending.updatePassword = false;
                this.pending.displayNewPassword = false;
            });
        },
    },
    computed: {
        /**
         * Teste si le nouveau mot de passe et le checkP mot de passe sont des contenus vide
         * 
         * @returns {Boolean}
         */
        passwordsFilled() {
            return (this.newPassword !== "" && this.checkPassword !== "");
        },
        /**
         * Teste si les deux nouveaux mots de passe sont different
         * 
         * @returns {Boolean}
        */
        notSamePasswords() {
            if (this.passwordsFilled) {
                return (this.newPassword !== this.checkPassword);
            }
            else {
                return false;
            }
        },

         /**
         * Teste si le nouveau mot de passe correspond bien au complexité requise avec les rules.regex
         *  --> Affiche un message d'erreur sinon
         * 
         * @returns {object}
         */
         passwordValidation() {
            let errors = [];
            for (let condition of this.rules) {
                if (!condition.regex.test(this.newPassword)) {
                    condition.succesId=false;
                    errors.push(condition);
                }else{
                    condition.succesId=true;
                    errors.push(condition);
                } 
            }

            if (errors.length === 0) {
                return { valid: true, errors };
            }
            else {
                return { valid: false, errors };
            }
        },
    }, 
    unmounted() {
        this.success = false;
    },
}

</script>