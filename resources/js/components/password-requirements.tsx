/**
 * Mesma regra de `Password::defaults()` (AppServiceProvider::boot) --
 * atualize os dois juntos se a regra mudar, senão o texto promete uma
 * coisa e o servidor exige outra.
 */
export default function PasswordRequirements() {
    return (
        <p id="password-requisitos" className="text-muted-foreground text-xs">
            Pelo menos 8 caracteres, com letra maiúscula, minúscula e um símbolo (ex.: <span aria-hidden="true">!@#$%</span>).
        </p>
    );
}
