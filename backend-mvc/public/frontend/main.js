document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("interventions-container");

  fetch("http://localhost:8888/exemple/backend-mvc/public/api/interventions")
    .then(response => {
      if (!response.ok) throw new Error("Erreur réseau");
      return response.json();
    })
    .then(data => {
      if (data.length === 0) {
        container.innerHTML = "<p>Aucune intervention trouvée.</p>";
        return;
      }

      const list = document.createElement("ul");

      data.forEach(item => {
        const li = document.createElement("li");
        li.innerHTML = `<strong>${item.nom}</strong> - ${item.description}`;
        list.appendChild(li);
      });

      container.innerHTML = "";
      container.appendChild(list);
    })
    .catch(err => {
      container.innerHTML = `<p>Erreur de chargement : ${err.message}</p>`;
    });
});