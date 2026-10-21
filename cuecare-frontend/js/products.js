document.addEventListener("DOMContentLoaded", () => {
  const modal = document.querySelector("#productModal");
  document
    .querySelectorAll("[data-modal-open]")
    .forEach((button) =>
      button.addEventListener("click", () =>
        document
          .getElementById(button.dataset.modalOpen)
          ?.classList.add("open"),
      ),
    );
  document
    .querySelectorAll("[data-modal-close]")
    .forEach((button) =>
      button.addEventListener("click", () =>
        button.closest(".modal-backdrop")?.classList.remove("open"),
      ),
    );
  modal?.addEventListener("click", (event) => {
    if (event.target === modal) modal.classList.remove("open");
  });
  document
    .querySelector("#productForm")
    ?.addEventListener("submit", (event) => {
      event.preventDefault();
      modal?.classList.remove("open");
      window.CueCareAPI?.toast("Product added to the demo catalog");
      event.target.reset();
    });
  document
    .querySelector("#adjustmentForm")
    ?.addEventListener("submit", (event) => {
      event.preventDefault();
      window.CueCareAPI?.toast("Stock adjustment saved to the demo log");
    });
  const search = document.querySelector("#productSearch");
  const category = document.querySelector("#categoryFilter");
  const filter = () => {
    const query = (search?.value || "").toLowerCase();
    const selected = category?.value || "all";
    document.querySelectorAll("#productTable tbody tr").forEach((row) => {
      const matchText = row.textContent.toLowerCase().includes(query);
      const matchCategory =
        selected === "all" || row.dataset.category === selected;
      row.style.display = matchText && matchCategory ? "" : "none";
    });
  };
  search?.addEventListener("input", filter);
  category?.addEventListener("change", filter);
  if (location.hash === "#add")
    document.querySelector("#productModal")?.classList.add("open");
});
