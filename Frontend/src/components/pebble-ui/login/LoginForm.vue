<template>
    <div v-if="user && !pending.user">
        <div class="d-grid gap-2">
            <button class="btn btn-outline-secondary d-flex align-items-center justify-content-between" @click="logout()">
                <span class="text-start">
                    <span class="lead d-block">Fermer ma session</span>
                    ({{userDisplayName}})
                </span>
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </div>

    <form method="post" action="/" @submit.prevent="login()" v-else-if="!user && !pending.user">
        <div class="alert alert-danger mb-3" v-if="error && displayError">{{error}}</div>

        <div class="form-floating mb-3">
            <input type="text" class="form-control form-control-lg" name="login" id="login_login" placeholder="name@example.com" v-model="username" required autofocus>
            <label for="login_login">Utilisateur / e-mail</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" class="form-control form-control-lg" name="password" id="login_password" placeholder="Passphrase" v-model="password" required>
            <label for="login_password">Mot de passe</label>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-lg btn-primary" type="submit" :disabled="pending.auth">
                <span v-if="pending.auth">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    Authentification...
                </span>
                <span v-else>Connexion</span>
            </button>
            <a href="/" class="text-secondary text-center">Mot de passe oublié</a>
        </div>

        <hr class="border-light">

        <div class="d-grid gap-2">
            <button @click="loginProvider('google')" type="button" class="btn btn-outline-secondary btn.lg"><i class="bi bi-google"></i> Connexion avec Google</button>
            <button @click="loginProvider('microsoft')" type="button" class="btn btn-outline-secondary btn.lg"><i class="bi bi-microsoft"></i> Connexion avec Microsoft</button>
        </div>
    </form>

    <Spinner label="Initialisation..." v-else />
</template>

<script>
import Spinner from '../Spinner.vue';

export default {
    props: {
        displayError: {
            type: Boolean,
            default: true
        }
    },

    data() {
        return {
            error: null,
            pending: {
                auth: false,
                user: false
            },
            username: "",
            password: "",
            user: null
        };
    },

    emits: ['error'],

    computed: {
        /**
         * Retourne un display name pour l'utilisateur actif en fonction des informations displayName et email
         * @returns {String}
         */
        userDisplayName() {
            if (this.user)
                return this.user.displayName ? this.user.displayName : this.user.email.split("@")[0];
            else
                return null;
        }
    },

    methods: {
        /**
         * Authentification à l'API
         * @returns {Promise}
         */
        login() {
            this.pending.auth = true;
            return this.$app.login(this, this.username, this.password)
            .catch((error) => {
                this.error = this.$app.catchError(error, {
                    mode: "message"
                });
                this.$emit("error", this.error);
            })
            .finally(() => {
                this.pending.auth = false;
            });
        },
        /**
         * Authentification à l'API via un prestataire externe
         * @returns {Promise}
         */
        loginProvider(provider) {
            return this.$app.loginProvider(provider)
            .catch((error) => {
                this.error = this.$app.catchError(error, {
                    mode: "message"
                });
                this.$emit("error", this.error);
            });
        },
        /**
         * Déconnecte l'utilisateur
         */
        logout() {
            this.$app.logout();
        }
    },
    mounted() {
        this.user = this.$app.firebase_user;
        
        this.$app.addEventListener("auth", () => {
            this.pending.auth = true;
        });

        this.$app.addEventListener("authError", (error) => {
            this.pending.auth = false;
            this.error = error;
        });

        this.$app.addEventListener("authInitializing", () => {
            this.pending.user = true;
        });

        this.$app.addEventListener("authInited", (user) => {
            this.user = user;
            this.pending.user = false;
        });

        this.$app.addEventListener("authCleared", () => {
            this.user = null;
        });
    },
    components: { Spinner }
}

</script>