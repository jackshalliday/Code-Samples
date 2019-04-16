using Graham.DataAccess;
using Graham.Messages.Commands.Insights;
using Graham.Messages.Commands.InstagramAccount;
using Graham.Messages.Commands.Python;
using Graham.Services;
using Graham.Services.Configuration;
using Graham.Services.Interfaces;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using NServiceBus;
using System;
using System.IO;
using System.Threading.Tasks;

namespace Graham.Backend
{
    public static class Program
    {
        static void Main(string[] args)
        {
            var builder = new ConfigurationBuilder()
                .SetBasePath(Directory.GetCurrentDirectory())
                .AddJsonFile("appsettings.json");

            var configuration = builder.Build();

            OnStart(configuration).GetAwaiter().GetResult();
        }

        static async Task OnStart(IConfigurationRoot configuration)
        {
            Console.Title = configuration["ApplicationName"];

            var endpointConfiguration = new EndpointConfiguration(configuration["ApplicationName"]);
            endpointConfiguration.ConfigureTransport();

            var services = new ServiceCollection()
                .AddScoped<InstagramAccountService>()
                .Configure<AppSettings>(configuration.GetSection("AppSettings"))
                .AddDbContext<GrahamContext>(options => options.UseSqlServer(configuration.GetConnectionString("DefaultConnection")));

            endpointConfiguration.UseContainer<ServicesBuilder>(
                customizations: customizations =>
                {
                    customizations.ExistingServices(services);
                });

            var endpointInstance = await Endpoint.Start(endpointConfiguration)
                .ConfigureAwait(false);

            services.AddSingleton<IMessageSession>(endpointInstance);

            Console.WriteLine("Press Enter to exit.");
            Console.ReadLine();

            await endpointInstance.Stop()
                .ConfigureAwait(false);
        }

        public static void ConfigureTransport(this EndpointConfiguration endpointConfiguration)
        {
            var transport = endpointConfiguration.UseTransport<LearningTransport>();
            transport.ConfigureRooting();
        }

        public static void ConfigureRooting(this TransportExtensions<LearningTransport> transport)
        {
            var routing = transport.Routing();
            routing.RouteToEndpoint(typeof(ValidateInstagramAccount), "Graham.Backend");
            routing.RouteToEndpoint(typeof(SendErrorMessageToInsights), "Graham.Backend");
            routing.RouteToEndpoint(typeof(ExecuteLoginScript), "Graham.Backend");
        }
    }
}
