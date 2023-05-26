<template>
	<div class="my-4" v-if="mdpid === 0">
		<button class="btn btn-outline-info" @click.prevent="cliquable()" :disabled="lock"> Mot de passe <i class="bi bi-key-fill ms-1"></i></button>
		<div class="alert alert-info mt-4" role="alert" v-if="success==true">Mot de passe modifié</div>
	</div>
		
    <article v-if="mdpid === 1 && !this.lock">
        <div class="input-group py-2 ">
            <input type="password" class="form-control" style="letter-spacing: 1px;" placeholder="Saisissez votre mot de passe actuel" v-model="oldPassword" @keydown.enter.prevent="verifOldPassword()">
            
            <button class="btn btn-outline-primary" type="button" @click="verifOldPassword()" :disabled="pending.displayOldPassword">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="pending.displayOldPassword"></span>
                    <i class="bi bi-check" v-else></i>
            </button>
            <button class="btn btn-outline-secondary bi bi-x" type="button" @click="resetDisplay()"></button>
        </div>
        <div class="alert alert-warning italic" role="alert" v-if="oldPasswordId"> {{oldPasswordError}}</div>
    </article>

</template>


<script>
import {getAuth, reauthenticateWithCredential, EmailAuthProvider } from '@firebase/auth';

export default {
	props:{
		lock: Boolean
	},

	data () {
		return {
			
			mdpid : 0,

            oldPassword:"",
			oldPasswordId:false,
			oldPasswordError:"",

			pending: {
				displayOldPassword: false,
			},
			success: false,
			Compteur:3
		}
	},

    emits:["step-change","edit-mode"],
    watch:{
		/**
		 * Teste la valeur du mdpid afin de renvoyer à l'élement parent une valeur bloquant ou non l'action des autres affichages de la fenetre
		 * 
		 * @param {Number} newVal
		 * 
		 * @return {object} 
		 */
        mdpid(newVal){
			if (newVal) {
				this.$emit('edit-mode',true);
			} else {
				this.$emit('edit-mode',false);
			}
        }
    },
	methods: {
		/**
		 * Action au moment du click en fonction de la vaiable de bloquage
		 */
		cliquable(){
			if(!this.lock){
				this.mdpid = 1;
			}
		},

		/**
		 * Verifie si le mot de passe est bien le mot de passe du login de firebase en utilisant la réauthentification
		 * 
		 * @return {Promise<UserCredential>}
		 */
		reauth() {
			let cred = EmailAuthProvider.credential(getAuth().currentUser.email, this.oldPassword);

			return reauthenticateWithCredential(getAuth().currentUser, cred)
			.then((userCred) => {
				return userCred;
			});
		},

		/**
		 * Verifie si le mot de passe entrer est bon, sinon renvoie des messages d'erreur sous forme de pop-up (grâce à oldPasswordId)
		 */
		verifOldPassword() {
			if(this.pending.displayOldPassword) return;
			this.pending.displayOldPassword = true;
			if(this.Compteur == 0){
				this.pending.displayOldPassword = false;
				this.oldPasswordError="Trop de tentatives. Veuillez réessayer plus tard";
			}else if(this.oldPassword==""){
				this.oldPasswordError="Veuillez entrer votre mot de passe";
				this.oldPasswordId=true;
				this.pending.displayOldPassword = false;
			}else {
				this.reauth()
				.then(() => {
					this.$emit("step-change",2)
					this.pending.displayOldPassword = false;
				})
				.catch(() => {
					this.oldPasswordError="Mot de passe incorrect. Plus que "+this.Compteur+" essais.";
					this.oldPasswordId=true;
					this.pending.displayOldPassword = false;
					this.Compteur--;
				});
			}
		},

		/**
		 * Reinitialise toutes les valeurs afin que aucune pop-up de default sois visible et que chaque displayPassword sois remis àzero à la saisie
		 */
		resetDisplay() {
			this.oldPassword="";
			this.mdpid = 0;
			this.oldPasswordId=false;
		}

	},
	unmounted(){
		this.success=false;
	}
}

</script>