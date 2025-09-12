import { Link } from '@inertiajs/react'
import { PropsWithChildren } from 'react'
import { Card, CardContent } from '@/components/ui/card'
import AppLogoIcon from '@/components/app-logo-icon'

export default function AuthSimpleLayout({ children }: PropsWithChildren) {
    return (
        <main className='grid min-h-screen place-content-center bg-background text-foreground'>
            <div className='w-full max-w-sm px-4'>
                <Card className='rounded-xl border-border/60'>
                    <CardContent className='p-8'>
                        <div className='mb-8 flex justify-center'>
                            <Link href='/'>
                                <AppLogoIcon className='h-8' />
                            </Link>
                        </div>
                        {children}
                    </CardContent>
                </Card>
            </div>
        </main>
    )
}