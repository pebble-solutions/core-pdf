<template>
    <div v-if="formUser && !lock">
        <div class="input-group py-2">
            <input type="text" class="form-control" :placeholder="displayName" v-model="newDisplayName" @keydown.enter.prevent="update()" autocomplete="off">
            <button class="btn btn-outline-primary" type="button" @click="update()" :disabled="pending.displayName">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="pending.displayName"></span>
                <i class="bi bi-check" v-else></i>
            </button>
            <button class="btn btn-outline-secondary bi bi-x" type="button" @click="clickClose()"></button>
        </div>
        
        <div class="alert alert-danger" role="alert" v-if="error">
            {{error}}
        </div>
    </div>

    <div  v-else>
        <button class="d-block fs-5 text-center w-100 btn btn-link py-2 text-secondary text-decoration-none" @click="cliquable()" :disabled="lock">
            <strong :class="labelClass">{{nameLabel}}</strong>
            <i class="bi bi-pen-fill ms-2"></i>
        </button>
        <div v-if="formUser">{{mail}}</div>
    </div>
</template>


<script>

import { getAuth, getIdToken } from '@firebase/auth';

export default {

    props: {
        displayName: String,
        lock: Boolean,
        mail: String
    },

    data() {
        return {
            error: null,
            newDisplayName: '',
            pending: {
                displayName: false
            },
            formUser: false
        }
    },
    emits:["edit-mode"],
    watch:{
        /**
         * Renvoie à l'élement parent un Object explicitant sur l'état du display Name afin de bloquer ou non les autres 
         * @param {Boolean} newVal 
         */
        formUser(newVal){
                this.$emit('edit-mode',newVal);
        }
    },

    computed: {
        /**
         * Retourne une classe italique lorsque le display name est vide.
         * 
         * @returns {String}
         */
        labelClass() {
            return !this.displayName ? 'fst-italic' : '';
        },

        /**
         * Retourne un nom d'utilisateur. 
         * - Si le displayName est vide, retourne
         * une valeur par défaut "?".
         * 
         * @returns {String}
         */
        nameLabel() {
            return !this.displayName ? 'Pseudo Non Défini' : this.displayName;
        }
    },

    methods: {
        
        /**
         * Action lors d'au click à l'ouverture. En fonction de la variable de bloquage des display, 
         * il modifie la valeur booleenne d'affichage du display name 
         */
        cliquable(){
            if(!this.lock){
                this.formUser = !this.formUser
            }
        },

        /**
         * Action lors d'au click à la fermeture. Réinitialise la valeur booleenne 
         * d'affichage du display name et celle des erreurs afin que aucune
         * erreur ne soit afficher après la fin d'utilisation
         */
        clickClose(){
            this.formUser=!this.formUser;
            this.error=null;
        },

        /**
         * Met à jour le display name au niveau de l'API firebase.
         */
        update() {

            // Si le status est déjà verrouiller, on empèche l'exécution de l'opération
            if(this.pending.displayName) return;

            this.pending.displayName = true;

            let newVal = this.newDisplayName.trim();

            if(!newVal){
                this.error = "Veuillez entrer un identifiant !";
                this.pending.displayName = false;
            }else if(newVal == this.displayName){
                this.error = "Franklin sait lacer ses chaussures et compter deux par deux et toi tu veux t'appeler deux fois pareil ? ";
                this.pending.displayName = false;
            }else {
                let auth = getAuth();
    
                getIdToken(auth.currentUser)
                .then((idToken) => {
                    // 1 : Envoyer une requête à l'API
                    return this.$app.ax.patch('http://localhost:3333/users/'+this.$app.firebase_user.uid, {
                        displayName: newVal,
                    },
                    {
                        headers: {
                            "Authorization" : "Bearer "+idToken
                        }
                    })
                })
                .then(() => {
                    this.$app.firebase_user.displayName = newVal;
        
                    this.$app.dispatchEvent("display-name-updated");
        
                    this.formUser = false;
                    this.error = null;
                })
                .catch(this.$app.catchError)
                .finally(() => {
                    this.pending.displayName = false;
                    this.newDisplayName="";
                });
            }
        }
    }
}
</script>