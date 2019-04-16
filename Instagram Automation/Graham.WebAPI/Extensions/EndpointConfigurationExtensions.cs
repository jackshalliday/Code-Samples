using Graham.Messages.Events.InstagramAccount;
using NServiceBus;

namespace Graham.WebAPI.Extensions
{
    public static class EndpointConfigurationExtensions
    {
        public static void ConfigureTransport(this EndpointConfiguration endpointConfiguration)
        {
            var transport = endpointConfiguration.UseTransport<LearningTransport>();
            transport.ConfigureRooting();
        }

        public static void ConfigureRooting(this TransportExtensions<LearningTransport> transport)
        {
            var routing = transport.Routing();
            routing.RouteToEndpoint(typeof(InstagramAccountAdded), "Graham.Backend");
        }
    }
}
