<!--
    App loader
    Ce composant affiche le spinner de chargement au dessus de la vue
-->

<template>
    <div class="app-main-loader">
        <div class="text-center my-5 text-secondary">
            <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status"></div>
            <div class="my-2 lead">Chargement des données...</div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.app-main-loader {
    position: fixed;
    top:0px; left:0px; right:0px; bottom:0px;
    background-color:rgba(255,255,255,0.8);
    z-index: 2000;
}
</style>