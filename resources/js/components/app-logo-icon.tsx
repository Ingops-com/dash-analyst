import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="/images/logo.png" // Asegúrate de que esta ruta coincida con tu imagen
            alt="Logo de la aplicación"
        />
    );
}